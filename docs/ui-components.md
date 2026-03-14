# UI Components Documentation

## Overview

Frontend telah direfactor menggunakan arsitektur component-based yang scalable dan maintainable.

## Struktur Folder

```
resources/
├── css/
│   ├── app.css           # Main stylesheet (imports all)
│   ├── variables.css     # CSS Variables
│   ├── globals.css       # Global styles & reset
│   └── theme/
│       ├── light.css     # Light theme
│       └── dark.css      # Dark theme (future ready)
│
└── views/components/
    ├── ui/               # Reusable UI Components
    │   ├── Button/
    │   ├── Input/
    │   ├── Card/
    │   ├── Badge/
    │   └── Modal/
    │
    ├── layout/           # Layout Components
    │   ├── Container/
    │   └── PageHeader/
    │
    └── ...               # Legacy components (backward compatible)
```

---

## UI Components

### Button

```blade
<x-ui.button
    variant="primary|secondary|outline|ghost|danger|success"
    size="sm|md|lg|icon"
    type="button|submit|reset"
    href="/url"
    icon="ri-user-line"
    iconPosition="left|right"
    :disabled="false"
    :loading="false"
    :full-width="false"
>
    Button Text
</x-ui.button>
```

**Examples:**
```blade
{{-- Primary Button --}}
<x-ui.button variant="primary">Submit</x-ui.button>

{{-- Button with Icon --}}
<x-ui.button variant="success" icon="ri-check-line">Save</x-ui.button>

{{-- Link Button --}}
<x-ui.button href="/users" variant="outline">Back</x-ui.button>

{{-- Loading State --}}
<x-ui.button :loading="true" disabled>Processing...</x-ui.button>
```

---

### Input

```blade
<x-ui.input
    name="email"
    label="Email Address"
    type="text|email|password|number|..."
    placeholder="Enter email"
    :value="$user->email"
    :required="true"
    :disabled="false"
    size="sm|md|lg"
    icon="ri-mail-line"
    iconPosition="left|right"
    helper="We'll never share your email"
/>
```

---

### Select

```blade
<x-ui.input.select
    name="role"
    label="User Role"
    :options="['admin' => 'Administrator', 'user' => 'User']"
    placeholder="Select role"
    :required="true"
/>
```

---

### Textarea

```blade
<x-ui.input.textarea
    name="description"
    label="Description"
    rows="4"
    resize="vertical|horizontal|both|none"
    helper="Max 500 characters"
/>
```

---

### Card

```blade
<x-ui.card
    variant="default|bordered|elevated|flat"
    size="sm|md|lg|xl"
    padding="none|sm|md|lg|xl"
    :hover="true"
    :clickable="true"
    href="/detail"
>
    <x-ui.card.header title="Card Title" subtitle="Description">
        <x-slot:action>
            <x-ui.button variant="ghost" size="icon">
                <i class="ri-more-2-fill"></i>
            </x-ui.button>
        </x-slot:action>
    </x-ui.card.header>
    
    <x-ui.card.body>
        Content here
    </x-ui.card.body>
    
    <x-slot:footer class="justify-end">
        <x-ui.button variant="secondary">Cancel</x-ui.button>
        <x-ui.button variant="primary">Save</x-ui.button>
    </x-slot:footer>
</x-ui.card>
```

---

### Badge

```blade
<x-ui.badge
    variant="primary|secondary|success|warning|danger|info|light|dark"
    size="sm|md|lg"
    :pill="false"
    :dot="false"
    icon="ri-check-line"
>
    Badge Text
</x-ui.badge>
```

**Examples:**
```blade
<x-ui.badge variant="success">Active</x-ui.badge>
<x-ui.badge variant="danger" dot>Offline</x-ui.badge>
<x-ui.badge variant="primary" :pill="true">New</x-ui.badge>
```

---

### Modal

```blade
<x-ui.modal
    name="confirm-delete"
    title="Confirm Delete"
    size="sm|md|lg|xl|full"
    :centered="true"
    :static="false"
>
    <x-slot:trigger>
        <x-ui.button variant="danger">Delete</x-ui.button>
    </x-slot:trigger>
    
    Are you sure you want to delete this item?
    
    <x-slot:footer>
        <x-ui.button variant="secondary" @click="open = false">Cancel</x-ui.button>
        <x-ui.button variant="danger">Confirm Delete</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
```

---

## Layout Components

### Container

```blade
<x-layout.container
    size="sm|md|lg|xl|full"
    padding="none|sm|md|lg"
    :centered="true"
>
    Content
</x-layout.container>
```

---

### Page Header

```blade
<x-layout.page-header
    title="Page Title"
    description="Page description"
    :breadcrumb="[
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'Users', 'url' => '/users'],
        ['label' => 'Create', 'active' => true],
    ]"
>
    <x-slot:actions>
        <x-ui.button variant="primary" icon="ri-add-line">Add New</x-ui.button>
    </x-slot:actions>
</x-layout.page-header>
```

---

## Legacy Components (Backward Compatible)

Komponen lama tetap dapat digunakan karena sudah di-redirect ke komponen baru:

```blade
{{-- Old (still works) --}}
<x-btn title="Submit" route="/save" color="primary" />
<x-form.input name="email" label="Email" />

{{-- New (recommended) --}}
<x-ui.button href="/save" variant="primary">Submit</x-ui.button>
<x-ui.input name="email" label="Email" />
```

---

## CSS Variables

Semua warna menggunakan CSS Variables untuk konsistensi:

```css
/* Primary (Dynamic from database) */
var(--color-primary)
var(--color-secondary)

/* Status Colors */
var(--color-success)
var(--color-error)
var(--color-warning)
var(--color-info)

/* Neutral Scale */
var(--color-gray-50) to var(--color-gray-900)

/* Spacing */
var(--space-1) to var(--space-24)

/* Border Radius */
var(--radius-sm) to var(--radius-full)

/* Shadows */
var(--shadow-sm) to var(--shadow-xl)
```

---

## Tailwind Config

Theme customization di `resources/css/app.css`:

```css
@theme {
    --color-primary: var(--client-color-primary, #1C3259);
    --color-secondary: var(--client-color-secondary, #F3F3F3);
    /* ... */
}
```

**Catatan:** Dynamic color dari database (`--client-color-primary`) tetap berfungsi.
