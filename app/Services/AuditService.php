<?php

namespace App\Services;

use App\Enums\AuditCategory;
use App\Enums\AuditSeverity;
use Carbon\CarbonInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditService
{
    private ?Client $httpClient;

    private ?string $projectId;

    private string $database = 'audit-logs';

    public function __construct()
    {
        $this->httpClient = app('audit.http_client');
        $this->projectId = config('services.google.project_id');
    }

    /**
     * Write an audit entry to Firestore.
     *
     * Pre-computes filter tags (e.g. "cat_transaction", "sev_normal", "cat_transaction_sev_normal")
     * to support efficient ARRAY_CONTAINS queries without composite indexes.
     *
     * @param  array{metadata?: array<string, mixed>, user_id?: int, user_name?: string, company_id?: int}  $context
     */
    public function log(
        AuditCategory $category,
        AuditSeverity $severity,
        string $action,
        string $description,
        array $context = [],
    ): void {
        if (! $this->httpClient) {
            return;
        }

        $userId = (int) ($context['user_id'] ?? auth()->id() ?? 0);
        $userName = $context['user_name'] ?? auth()->user()?->name;
        $companyId = (int) ($context['company_id'] ?? Request::input('company_id') ?? 0);
        $metadata = $context['metadata'] ?? [];

        $cat = $category->value;
        $sev = $severity->value;
        $filterTags = ["cat_{$cat}", "sev_{$sev}", "cat_{$cat}_sev_{$sev}"];

        try {
            $this->httpClient->post(
                "v1/projects/{$this->projectId}/databases/{$this->database}/documents/audit_logs",
                [
                    'json' => [
                        'fields' => $this->encodeFields([
                            'user_id' => $userId,
                            'user_name' => $userName,
                            'company_id' => $companyId,
                            'category' => $cat,
                            'severity' => $sev,
                            'action' => $action,
                            'description' => $description,
                            'metadata' => empty($metadata) ? null : json_encode($metadata),
                            'ip_address' => Request::ip(),
                            'created_at' => now()->getTimestamp(),
                            'expires_at' => now()->addDays(7),
                            'filter_tags' => $filterTags,
                        ]),
                    ],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Audit log write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{next_cursor: ?int}}
     */
    public function getFilteredLogs(?int $companyId, array $filters, int $limit = 25): array
    {
        if (! $this->httpClient) {
            return ['data' => [], 'meta' => ['next_cursor' => null]];
        }

        try {
            $where = [];

            if ($companyId) {
                $where[] = $this->fieldFilter('company_id', 'EQUAL', (int) $companyId);
            }

            $filterTag = $this->buildFilterTag($filters);
            if ($filterTag) {
                $where[] = $this->fieldFilter('filter_tags', 'ARRAY_CONTAINS', $filterTag);
            }

            if (! empty($filters['date_from'])) {
                $where[] = $this->fieldFilter('created_at', 'GREATER_THAN_OR_EQUAL', strtotime($filters['date_from']));
            }

            if (! empty($filters['date_to'])) {
                $where[] = $this->fieldFilter('created_at', 'LESS_THAN_OR_EQUAL', strtotime($filters['date_to'].' 23:59:59'));
            }

            $structuredQuery = [
                'from' => [['collectionId' => 'audit_logs']],
                'orderBy' => [['field' => ['fieldPath' => 'created_at'], 'direction' => 'DESCENDING']],
                'limit' => $limit,
            ];

            if (count($where) === 1) {
                $structuredQuery['where'] = $where[0];
            } elseif (count($where) > 1) {
                $structuredQuery['where'] = [
                    'compositeFilter' => [
                        'op' => 'AND',
                        'filters' => $where,
                    ],
                ];
            }

            if (! empty($filters['cursor'])) {
                $structuredQuery['startAt'] = [
                    'values' => [$this->encodeValue((int) $filters['cursor'])],
                    'before' => false,
                ];
            }

            $response = $this->httpClient->post(
                "v1/projects/{$this->projectId}/databases/{$this->database}/documents:runQuery",
                ['json' => ['structuredQuery' => $structuredQuery]]
            );

            $results = json_decode($response->getBody()->getContents(), true);
            $logs = [];
            $lastCursor = null;

            foreach ($results as $result) {
                if (! isset($result['document'])) {
                    continue;
                }

                $doc = $result['document'];
                $data = $this->decodeFields($doc['fields'] ?? []);
                $data['id'] = basename($doc['name']);
                unset($data['filter_tags']);
                $logs[] = $data;
                $lastCursor = $data['created_at'] ?? null;
            }

            return [
                'data' => $logs,
                'meta' => [
                    'next_cursor' => count($logs) === $limit ? $lastCursor : null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Audit log query failed', ['error' => $e->getMessage()]);

            return ['data' => [], 'meta' => ['next_cursor' => null]];
        }
    }

    /**
     * Build the appropriate filter tag for ARRAY_CONTAINS query.
     */
    private function buildFilterTag(array $filters): ?string
    {
        $category = $filters['category'] ?? null;
        $severity = $filters['severity'] ?? null;

        return match (true) {
            $category && $severity => "cat_{$category}_sev_{$severity}",
            (bool) $category => "cat_{$category}",
            (bool) $severity => "sev_{$severity}",
            default => null,
        };
    }

    /**
     * Encode an associative array into Firestore REST API field format.
     *
     * @return array<string, array<string, mixed>>
     */
    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    private function encodeValue(mixed $value): array
    {
        return match (true) {
            $value === null => ['nullValue' => null],
            is_bool($value) => ['booleanValue' => $value],
            $value instanceof CarbonInterface => ['timestampValue' => $value->toIso8601String()],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_array($value) => ['arrayValue' => ['values' => array_map([$this, 'encodeValue'], $value)]],
            default => ['stringValue' => (string) $value],
        };
    }

    /**
     * Decode Firestore REST API fields back to an associative array.
     *
     * @return array<string, mixed>
     */
    private function decodeFields(array $fields): array
    {
        $data = [];
        foreach ($fields as $key => $wrapper) {
            $data[$key] = $this->decodeValue($wrapper);
        }

        return $data;
    }

    private function decodeValue(array $wrapper): mixed
    {
        return match (true) {
            array_key_exists('nullValue', $wrapper) => null,
            isset($wrapper['booleanValue']) => $wrapper['booleanValue'],
            isset($wrapper['integerValue']) => (int) $wrapper['integerValue'],
            isset($wrapper['doubleValue']) => $wrapper['doubleValue'],
            isset($wrapper['timestampValue']) => $wrapper['timestampValue'],
            isset($wrapper['stringValue']) => $wrapper['stringValue'],
            isset($wrapper['arrayValue']) => array_map([$this, 'decodeValue'], $wrapper['arrayValue']['values'] ?? []),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldFilter(string $field, string $op, mixed $value): array
    {
        return [
            'fieldFilter' => [
                'field' => ['fieldPath' => $field],
                'op' => $op,
                'value' => $this->encodeValue($value),
            ],
        ];
    }
}
