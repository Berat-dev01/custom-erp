# Admin Panel — Upgrade Notes

## Breaking Change: Layout Slots (apply after pulling this update)

The `app.blade.php` layout no longer contains any project-specific code
(CRM routes, notification bell, role array, navigation items).
It now exposes three named stack slots that each project fills via its own layout.

**What you need to do in your project:**

### 1. Publish the updated config

```bash
php artisan vendor:publish --tag=admin-panel-config --force
```

Then open `config/admin-panel.php` and add your project's role mapping:

```php
'roles' => [
    // 'role_name' => ['Display Label', 'badge-variant'],
    // e.g. 'superadmin' => ['Superadmin', 'primary'],
],
'default_role' => ['Staff', 'secondary'],
```

### 2. Create a project-specific layout

Create `resources/views/layouts/admin-app.blade.php` (or any name you prefer):

```blade
@extends('admin-panel::layouts.app')

@push('sidebar-nav')
    {{-- Your project's sidebar navigation items --}}
    {{-- Example: --}}
    <div class="sidebar-section-label">My Module</div>
    <x-admin-panel::sidebar-item route="mymodule.index" icon="box" label="Items" />
@endpush

@push('topbar-actions')
    {{-- Optional: notification bell, custom buttons, etc. --}}
@endpush

@push('command-palette-footer')
    {{-- Optional: full-text search shortcut inside the command palette --}}
@endpush
```

### 3. Update your views to extend the new layout

Replace all occurrences of:
```blade
@extends('admin-panel::layouts.app')
```
with:
```blade
@extends('layouts.admin-app')
```

(or whatever you named your layout in step 2)

Quick find-and-replace in your project:
```bash
grep -rl "@extends('admin-panel::layouts.app')" resources/views/ | \
  xargs sed -i '' "s/@extends('admin-panel::layouts.app')/@extends('layouts.admin-app')/"
```

### 4. If you had project-specific nav in the old app.blade.php

Move it into your new project layout's `@push('sidebar-nav')` block.
The layout's nav section is now entirely driven by what projects push into that stack.

---

### What the three stack slots do

| Stack | Purpose |
|---|---|
| `@stack('sidebar-nav')` | Additional sidebar sections below the Dashboard item |
| `@stack('topbar-actions')` | Icons/buttons in the topbar right area (before the profile dropdown) |
| `@stack('command-palette-footer')` | A form/button at the bottom of the ⌘K command palette |
