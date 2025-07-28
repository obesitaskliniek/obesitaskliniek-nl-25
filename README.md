# NOK 2025 Theme - Page Parts System
&copy; 2025 Klaas Leussink

## Overview

The basic idea of this theme is to build pages using reusable components called "page parts". This allows for a flexible page structure where each page can be composed of various sections or components, making it easier to manage and update content.

## Current System

### Page Parts
- **Custom Post Type**: `page_part` for creating reusable page components
- **Template System**: Templates defined in `/template-parts/page-parts/` consisting of:
    - PHP file containing template structure and custom field definitions
    - CSS file containing component-specific styles
- **Dynamic Custom Fields**: Defined in template headers using either:
  ```php
  // Single line format
  * Custom Fields: tagline:text,button_text:text,button_url:url
  
  // Multi-line format (preferred for readability)
  * Custom Fields:
  * - tagline:text
  * - button_text:text
  * - button_url:url
  ```

### Editor Experience
- **React-based Interface**: Custom Gutenberg sidebar with template selection and dynamic form fields
- **Live Preview System**: Real-time iframe preview with smart debouncing (500ms)
- **Unified Transient System**: Single transient stores complete editor state (title, content, excerpt, meta)
- **Smart Change Detection**: Distinguishes user changes from autosaves to prevent unnecessary updates

### Current Implementation Status
✅ **Fully Functional**:
- Page part creation and editing
- Template system with dynamic custom fields
- Live preview with real-time updates
- Title/content changes properly reflected in preview
- Performance optimized (fixed 5-10 second save delays)
- Support for field types: text, textarea, url, repeater (JSON), select, checkbox

## TODO List

### 1. SEO Integration (HIGH PRIORITY)
**Problem**: SEO tools (Yoast) can't analyze page part content since it's not in the main post content.

**Solution**: Create Gutenberg blocks that represent page parts
- [ ] Create `nok/page-part` Gutenberg block registration
- [ ] Build React component for block editor (dropdown selection + preview)
- [ ] Implement content sync: when page part updates → update all blocks using it
- [ ] Add frontend replacement: block HTML → full page part output
- [ ] Estimated effort: ~300-400 lines of code, 1-2 days

### 2. Cache Invalidation System (MEDIUM PRIORITY)
**Goal**: Efficiently clear caches when page parts change

- [ ] Add `_used_in_pages` meta field to track which pages use each page part
- [ ] Auto-update register when page part blocks are added/removed from pages
- [ ] Create cache invalidation hook: page part saves → clear cache for registered pages only
- [ ] Benefits: Performance improvement, usage tracking, cleanup warnings

### 3. Various (MEDIUM PRIORITY)
- [ ] Work out how JSON string is populated by the user in repeater fields.

### 4. Bug Fixes
- [x] ~~Fixed: Memory exhaustion during save (infinite recursion in `save_editor_state`)~~
- [x] ~~Fixed: Preview not updating when reverting title/content to original values~~
- [x] ~~Fixed: Multiline custom field definitions not working~~
- [ ] Fix: autosave warning keeps coming up, because we're using it to update the preview frame. This could lead to confusion about which version is the "real" one, and it's annoying.

### 5. Technical Debt
- [x] ~~Cleaned up duplicate `wp_localize_script()` calls~~
- [x] ~~Updated `embed_page_part_callback()` to use unified transient system~~
- [x] ~~Removed debug logging for production~~

## Technical Architecture

### Key Components
1. **Theme.php**: Main orchestrator handling registration, meta management, preview system
2. **React Components**: Design selector with debounced field updates and preview triggering
3. **Preview System**: Iframe-based with smart update detection and transient filtering
4. **Template System**: Dynamic field generation from template headers
5. **Unified Transient System**: Single source of truth for all editor state during preview

### Custom Field Types Supported
- `text`: Single line text input
- `textarea`: Multi-line text input
- `url`: URL input with validation
- `repeater`: JSON array storage (displayed as textarea for now)
- `select`: Dropdown selection with optional nice names for each option
- `checkbox`: Checkbox input (1 or 0)

### Performance Features
- 500ms debouncing on field changes
- User vs autosave change detection
- 2-second initialization delay to prevent server spam
- Single database update during save (prevents infinite recursion)
- Transient-based preview system with 5-minute expiration

## Future Enhancements (Nice to Have)
- [ ] Enhanced repeater field UI (proper array management interface)
- [ ] Image field type support
- [ ] Template preview thumbnails in dropdown
- [ ] Bulk operations for page parts
- [ ] Import/export functionality for templates
- [ ] Version history for page parts

## Development Notes
- System uses WordPress-native APIs wherever possible for future-proofing
- Custom file parser handles multiline template headers (fallback from `get_file_data()`)
- All custom fields are properly sanitized based on their declared types
- REST API endpoint (`/wp-json/nok-2025-v1/v1/embed-page-part/{id}`) for iframe previews