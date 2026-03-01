<?php

return [

    // Auth
    'logged_out' => 'Logged out',
    'password_confirmed' => 'Password confirmed',
    'verification_email_failed' => 'There was a problem sending the verification email. Please try again.',

    // Two-Factor Authentication
    'two_factor_enabled' => '2FA enabled, please confirm',
    'two_factor_confirmed' => '2FA confirmed',
    'two_factor_disabled' => '2FA disabled',
    'two_factor_not_enabled' => '2FA not enabled',
    'recovery_codes_regenerated' => 'Recovery codes regenerated',
    'two_factor_code_or_recovery_required' => 'Please provide a code or recovery code.',
    'invalid_recovery_code' => 'The provided recovery code was invalid.',
    'invalid_two_factor_code' => 'The provided two factor authentication code was invalid.',

    // Email Verification
    'email_already_verified' => 'Email already verified',
    'verification_link_sent' => 'Verification link sent',
    'email_verified' => 'Email verified successfully',
    'email_updated_verified' => 'Email address updated and verified successfully',
    'invalid_verification_link' => 'Invalid verification link',
    'invalid_expired_verification_link' => 'Invalid or expired verification link',

    // Team Members
    'member_invited' => 'Member invited successfully',
    'member_updated' => 'Member updated successfully',
    'member_promoted' => 'Member role updated successfully',
    'full_access' => 'Full Access',
    'wallets' => 'Wallets',

    // Profile
    'profile_updated' => 'Profile updated successfully',
    'profile_updated_pending_email' => 'Profile updated. A verification link has been sent to your new email address.',
    'pending_email_cancelled' => 'Pending email change cancelled',
    'account_deleted' => 'Account deleted successfully',
    'password_updated' => 'Password updated successfully',

    // Invitations
    'invitation_accepted' => 'Account activated successfully',

    // Authorization
    'unauthorized_company' => 'Unauthorized access to company.',

    // Transfers
    'transfer_initiated' => 'Transfer initiated successfully.',
    'transfer_reviewed' => 'Transfer reviewed successfully.',
    'insufficient_funds' => 'Insufficient available funds.',
    'cross_currency_not_permitted' => 'Cross-currency transfers are not permitted at this stage.',
    'transfer_already_reviewed' => 'This transaction has already been reviewed.',
    'transfer_cancelled' => 'Transfer cancelled successfully.',
    'transfer_not_pending' => 'This transaction is not pending approval.',
    'daily_limit_exceeded' => 'This transfer would exceed your daily transaction limit.',
    'idempotency_key_required' => 'A valid Idempotency-Key header is required.',

    // Notifications
    'notification_transaction_pending_approval' => 'Transaction Pending Approval',
    'notification_transaction_approved' => 'Transaction Approved',
    'notification_transaction_rejected' => 'Transaction Rejected',
    'notification_transaction_completed' => 'Transaction Completed',
    'unknown' => 'Unknown',

    // Audit Trail
    'audit' => [
        'user_login' => 'User logged in successfully.',
        'user_login_failed' => 'Failed login attempt.',
        'user_logout' => 'User logged out.',
        'user_registered' => 'New user registered.',
        'user_2fa_enabled' => 'Two-factor authentication enabled.',
        'user_2fa_disabled' => 'Two-factor authentication disabled.',
        'user_password_changed' => 'Password changed.',
        'user_password_reset' => 'Password reset via email link.',
        'transfer_initiated' => 'Transfer initiated.',
        'transfer_approved' => 'Transfer approved.',
        'transfer_rejected' => 'Transfer rejected.',
        'transfer_cancelled' => 'Transfer cancelled.',
        'team_member_invited' => 'Team member invited.',
        'team_member_updated' => 'Team member updated.',
        'team_member_removed' => 'Team member removed.',
        'team_member_promoted' => 'Team member role changed.',
        'wallet_created' => 'Wallet created.',
        'wallet_updated' => 'Wallet updated.',
        'wallet_deleted' => 'Wallet deleted.',
        'wallet_freeze_toggled' => 'Wallet freeze status changed.',
        'threshold_changed' => 'Company threshold updated.',
        'threshold_deleted' => 'Company threshold deleted.',
        'security_threshold_changed' => 'Security threshold changed.',
        'profile_updated' => 'Profile updated.',
        'account_deleted' => 'Account deleted.',
    ],

];
