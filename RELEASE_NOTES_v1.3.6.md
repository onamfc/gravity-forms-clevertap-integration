# CleverTap Gravity Forms Integration - Release v1.3.6

## 🚀 What's New

### Fixed Event Data Mappings
We've fixed a critical issue with Event Data Mappings that was limiting their functionality. Previously, event data values were incorrectly tied to form fields, but now you can create truly custom key-value pairs for your CleverTap events.

## 🔧 Key Improvements

### ✅ Custom Key-Value Pairs for Events
- **Before**: Event data mappings were limited to form field values
- **After**: Create custom static values like:
  - `lead_source` → `"Google Ads"`
  - `campaign` → `"Summer 2024"`
  - `referrer` → `"Facebook"`
  - `source` → `"Newsletter"`

### ✅ Clearer User Interface
- Improved labels and descriptions to distinguish between:
  - **Property Mappings**: Map form fields to CleverTap profile properties
  - **Event Data Mappings**: Create custom key-value pairs for event context
- Enhanced validation messages for better user guidance

### ✅ Better User Experience
- Simplified event data configuration process
- More intuitive form interface
- Clearer error messaging when validation fails

## 🎯 Use Cases

This update enables powerful event tracking scenarios:

```json
{
  "evtName": "Newsletter Signup",
  "evtData": {
    "form_id": 5,
    "lead_source": "Google Ads",
    "campaign": "Q1 2024 Growth",
    "landing_page": "pricing",
    "referrer": "organic_search"
  }
}
```

## 🔄 Migration Notes

- **Existing configurations**: No action required - all existing setups continue to work
- **New installations**: Benefit from improved interface immediately
- **Backward compatibility**: Fully maintained

## 📋 Technical Details

- Fixed form settings template to use text inputs for event data values
- Updated JavaScript validation logic
- Improved admin interface clarity
- Enhanced error handling and user feedback

## 🚀 Getting Started

1. **Update the plugin** to v1.3.6
2. **Edit your forms** in Gravity Forms
3. **Go to CleverTap Integration** settings
4. **Add Event Data Mappings** with custom key-value pairs
5. **Save and test** your configuration

## 📞 Support

If you encounter any issues or have questions about this update, please check the plugin documentation or contact support.

---

**Plugin Version**: 1.3.6  
**Release Date**: January 27, 2025  
**Compatibility**: WordPress 5.0+, Gravity Forms 2.0+, PHP 7.4+