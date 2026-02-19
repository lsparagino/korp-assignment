---
description: Testing Workflow
---

## Testing Workflow

### Unit and Integration Tests

- Test critical logic first
- Split the code if needed to make it testable

### Browser Testing

1. Navigate to the relevant page
2. Wait for content to load completely
3. Test primary user interactions
4. Test secondary functionality (error states, edge cases)
5. Check the JS console for errors or warnings
   - If you see errors, investigate and fix them immediately
   - If you see warnings, document them and consider fixing if they affect user experience
6. Document any bugs found and fix them immediately