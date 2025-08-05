# Changelog

## [1.3.6]

### Fixed
- **Event Data Mappings**: Fixed event data mappings to allow custom key-value pairs instead of being tied to form fields
- Users can now create static event data like "lead_source" → "Google Ads" or "campaign" → "Summer 2024"
- Improved validation and error messaging to distinguish between property mappings (form field → CleverTap property) and event data mappings (custom key-value pairs)
- Enhanced user interface clarity for event data configuration

### Technical Changes
- Updated form settings template to use text input for event data values instead of form field dropdown
- Simplified JavaScript validation logic for event data mappings
- Improved admin interface labels and descriptions for better user experience

---

## [1.3.5] - Previous Release
- Flexible property mapping system
- Custom event names per form
- Enhanced form settings interface
- Comprehensive testing suite
- Improved error handling and logging

## [1.3.0] - Previous Release
- Added property mappings functionality
- Database schema migrations
- Modern Gravity Forms settings integration

## [1.2.0] - Previous Release
- Custom event names
- Delayed event sending
- Enhanced API error handling

## [1.1.0] - Previous Release
- Form-specific configurations
- Admin settings page
- Connection testing

## [1.0.0] - Initial Release
- Basic CleverTap integration
- Profile updates with tags
- Event sending functionality