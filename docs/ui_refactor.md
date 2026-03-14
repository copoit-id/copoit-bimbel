# Frontend Refactor Guide (Scalable & Maintainable)

## Objective

Rapikan frontend yang sudah ada **tanpa mengubah tampilan desain saat ini**.

Fokus refactor adalah:

* Konsistensi design
* Component-based architecture
* Scalability
* Maintainability
* Theme-ready system
* Reusable components
* Dynamic variables

Refactor ini **tidak bertujuan mengubah design**, hanya **merapikan struktur dan membuat sistem yang lebih scalable**.

---

# ⚠️ IMPORTANT NOTE (WAJIB DIIKUTI)

Saat melakukan refactor:

1. **JANGAN mengubah layout yang sudah ada**
2. **JANGAN mengubah style visual yang sekarang**
3. **JANGAN mengubah spacing, typography, atau visual feel**
4. **JANGAN mengganti warna yang sekarang digunakan**
5. **Gunakan warna yang sama seperti di sistem sekarang**
6. **Primary color tetap dinamis seperti sistem yang sekarang**
7. **Semua warna yang berasal dari database harus tetap dinamis**
8. **Jangan mengubah logic dynamic color yang sudah ada**

Tujuan refactor **hanya membuat struktur code lebih konsisten**, bukan mengubah UI.

---

# Core Principles

Gunakan prinsip berikut:

* Component Driven Development
* Separation of Concerns
* Reusable UI
* Theme-based Styling
* Centralized Variables
* Minimal Code Duplication

Semua UI harus **dipisah menjadi component reusable**.

---

# Architecture Principles

Frontend harus dibuat dengan tujuan:

* Mudah di-maintain
* Mudah di-scale
* Mudah ganti theme di masa depan
* Mudah menambah component baru
* Tidak ada code duplication

---

# Folder Structure (Best Practice)

Gunakan struktur seperti ini:

```
src
│
├── components
│   ├── ui
│   │   ├── Button
│   │   │   ├── Button.tsx
│   │   │   ├── Button.types.ts
│   │   │   └── Button.styles.ts
│   │   │
│   │   ├── Card
│   │   ├── Badge
│   │   ├── Input
│   │   └── Modal
│   │
│   └── layout
│       ├── Navbar
│       ├── Sidebar
│       └── Container
│
├── features
│   ├── dashboard
│   ├── exam
│   ├── materi
│   └── tryout
│
├── styles
│   ├── globals.css
│   ├── variables.css
│   ├── theme
│   │   ├── light.css
│   │   └── dark.css
│
├── hooks
├── utils
├── constants
└── types
```

Tujuan:

* UI reusable di `components/ui`
* Layout di `components/layout`
* Feature logic di `features`
* Theme system di `styles/theme`

---

# Component Rules

Semua UI harus:

* reusable
* configurable via props
* tidak hardcode
* mudah digunakan ulang

Contoh penggunaan:

```
<Button variant="primary" size="lg">
Submit
</Button>
```

Jangan menulis UI berulang seperti:

```
<button class="px-5 py-3 bg-green-500">
Submit
</button>
```

---

# Styling System

Gunakan **3 layer styling**.

## 1. Global Style

Untuk:

* font
* base reset
* base layout

File:

```
styles/globals.css
```

---

## 2. Theme Variables

Semua warna harus menggunakan **CSS Variables** agar mudah diganti.

Contoh:

```
:root {
  --color-primary: var(--dynamic-primary);
  --color-bg: #ffffff;
  --color-text: #111827;
}
```

---

# ⚠️ Dynamic Color Rules

Jika warna berasal dari database (misalnya primary color):

* **HARUS tetap dinamis**
* **JANGAN di-hardcode**
* Gunakan variable atau binding yang sama seperti sistem sekarang

Contoh:

```
--dynamic-primary
```

atau binding JS dari database.

Jangan mengganti sistem dynamic color yang sudah berjalan.

---

# Component Specific Styling

Component boleh memiliki styling sendiri jika spesifik.

Contoh:

```
Button.styles.ts
Card.styles.ts
```

Ini membantu maintenance lebih mudah.

---

# Theme System (Future Ready)

Walaupun theme lain belum digunakan sekarang, struktur harus **siap untuk masa depan**.

Contoh:

```
styles/theme/light.css
styles/theme/dark.css
```

Contoh light theme:

```
:root {
  --color-bg: #ffffff;
  --color-text: #111827;
}
```

Contoh dark theme:

```
[data-theme="dark"] {
  --color-bg: #0f172a;
  --color-text: #f8fafc;
}
```

Theme bisa diganti dengan:

```
document.documentElement.dataset.theme = "dark"
```

---

# Color Rules

Semua warna harus lewat variable.

SALAH:

```
bg-green-500
text-gray-900
```

BENAR:

```
bg-[var(--color-primary)]
text-[var(--color-text)]
```

Atau melalui config Tailwind.

---

# Reusable Component Strategy

Jika UI muncul **lebih dari 2 kali**, wajib dijadikan component.

Contoh base UI:

* Button
* Input
* Badge
* Card
* Modal
* Dropdown
* Tabs
* Avatar

---

# Variant System

Component harus memiliki variasi melalui props.

Contoh Button:

```
variant:
- primary
- secondary
- outline
- ghost

size:
- sm
- md
- lg
```

---

# Import Strategy

Gunakan path clean.

Benar:

```
import { Button } from "@/components/ui/Button"
```

Hindari:

```
../../../../components/ui/Button
```

---

# Code Duplication Rule

Jika ada UI yang sama muncul berkali-kali:

❌ Jangan copy paste.

✔ Jadikan reusable component.

Contoh:

```
<Card />
```

Lalu variasi menggunakan props.

---

# Scalability Rules

Struktur harus siap untuk:

* penambahan fitur baru
* penambahan component
* perubahan theme
* tim developer yang lebih besar

---

# Expected Refactor Result

Hasil akhir harus:

* Tampilan **tetap sama**
* UI **tidak berubah**
* Code **lebih rapi**
* Component **reusable**
* Theme **future ready**
* Folder **best practice industri**
* Mudah maintenance
* Mudah scaling

---

# Final Instruction

Saat melakukan refactor:

1. Jangan ubah layout
2. Jangan ubah visual UI
3. Gunakan component reusable
4. Gunakan variable untuk semua warna
5. Pertahankan dynamic color dari database
6. Hindari code duplication
7. Gunakan folder structure best practice
8. Siapkan theme system untuk masa depan
9. Fokus pada **consistency & scalability**
