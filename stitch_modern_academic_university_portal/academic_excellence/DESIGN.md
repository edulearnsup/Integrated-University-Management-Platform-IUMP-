---
name: Academic Excellence
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#44474c'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#75777d'
  outline-variant: '#c5c6cd'
  surface-tint: '#525f75'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#0e1c2f'
  on-primary-container: '#77849c'
  inverse-primary: '#bac7e1'
  secondary: '#775a00'
  on-secondary: '#ffffff'
  secondary-container: '#fece57'
  on-secondary-container: '#735700'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#001c38'
  on-tertiary-container: '#6886ae'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d6e3fe'
  primary-fixed-dim: '#bac7e1'
  on-primary-fixed: '#0e1c2f'
  on-primary-fixed-variant: '#3a475c'
  secondary-fixed: '#ffdf98'
  secondary-fixed-dim: '#eec14b'
  on-secondary-fixed: '#251a00'
  on-secondary-fixed-variant: '#5a4300'
  tertiary-fixed: '#d3e4ff'
  tertiary-fixed-dim: '#aac9f4'
  on-tertiary-fixed: '#001c38'
  on-tertiary-fixed-variant: '#29486d'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-hero:
    fontFamily: Playfair Display
    fontSize: 56px
    fontWeight: '600'
    lineHeight: 64px
    letterSpacing: -0.02em
  display-hero-mobile:
    fontFamily: Playfair Display
    fontSize: 36px
    fontWeight: '600'
    lineHeight: 44px
    letterSpacing: -0.01em
  headline-lg:
    fontFamily: Playfair Display
    fontSize: 40px
    fontWeight: '600'
    lineHeight: 48px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Playfair Display
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Playfair Display
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Playfair Display
    fontSize: 22px
    fontWeight: '500'
    lineHeight: 28px
    letterSpacing: 0em
  title-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 30px
    letterSpacing: -0.005em
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: 0em
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: 0.005em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.06em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 2rem
  gutter-mobile: 1rem
  margin: 3rem
  margin-mobile: 1.25rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style
The design system projects intellectual rigor, historical prestige, and modern research excellence, drawing inspiration from institutions like Oxford, ETH Zurich, and Stanford. It balances heritage with forward-looking precision.

### Target Audience & Persona
- **Audiences:** Global scholars, prospective undergraduate and postgraduate candidates, institutional donors, faculty, research partners, and international press.
- **Tone:** Authoritative, stately, precise, discerning, and welcoming without sacrificing intellectual gravitas.
- **Visual Style:** Minimalist Editorial Classicism. The interface prioritizes generous margins, immaculate typography, razor-sharp structural alignments, and purposeful restraint over decorative clutter.

## Colors
The palette balances commanding academic authority with legibility and warmth.

- **Primary (`#0B192C`):** Deep Chancellor Navy. Serves as the primary ground for text, headers, primary buttons, and institutional branding elements.
- **Secondary (`#C59B27`):** Academic Gold. Applied selectively to draw attention to honors, active indicators, high-level accolades, and prominent calls to action. Never used in large flood fills.
- **Tertiary (`#1E3E62`):** Oxford Slate Blue. Utilized for secondary action states, sub-navigation active items, badges, and deep interactive states.
- **Neutral (`#F8FAFC`):** Crisp Parchment. Acts as the primary page background, establishing clean contrast against pure white cards (`#FFFFFF`) and slate borders (`#E2E8F0`).
- **Surface & Functional Rules:** High-contrast accessibility (WCAG AAA for primary body copy) is strictly enforced. Body copy sits at `#1E293B` on pure white or `#F8FAFC` canvases.

## Typography
The system juxtaposes the scholarly gravitas of transitional serif headlines with the objective clarity of an engineered neo-grotesque sans-serif.

- **Editorial Headlines (`Playfair Display`):** Reserved for institutional titles, publication mastheads, department names, and hero banners. Provides distinct intellectual atmosphere.
- **Interface & Body (`Inter`):** Applied to long-form research prose, tables, interactive controls, dashboards, metadata, and wayfinding.
- **Tracking & Proportion:** Display headlines use slight negative tracking (`-0.01em` to `-0.02em`) for editorial cohesion. Metadata labels and uppercase subheadings employ positive tracking (`0.04em` to `0.06em`) for legibility in dense academic catalogs.

## Layout & Spacing
The layout relies on a strict 8px spatial grid combined with a classical 12-column responsive architecture.

- **Grid Architecture:** Desktop displays standard 12 columns with a max content container of `1280px` (`1440px` for dense data/research archives). Tablet uses 8 columns; mobile collapses to 4 columns.
- **Rhythm & Cadence:** Generous margins separate intellectual domains. Content sections are divided by clear whitespace rather than heavy lines.
- **Breakpoints:**
  - `Desktop (≥ 1024px)`: Full 12-column system, 48px canvas margin, 32px gutters.
  - `Tablet (768px – 1023px)`: 8-column layout, 32px canvas margin, 24px gutters.
  - `Mobile (≤ 767px)`: 4-column layout, 20px canvas margin, 16px gutters.

## Elevation & Depth
Elevation is understated and architectural. Depth is created primarily through tonal layering and hair-thin structural borders rather than heavy drop shadows.

- **Surface Tiers:**
  - **Base Canvas:** `#F8FAFC`
  - **Surface Level 1 (Cards, Modules):** `#FFFFFF` with a 1px border of `#E2E8F0`
  - **Surface Level 2 (Elevated Flyouts, Dropdowns):** `#FFFFFF` paired with an ambient shadow
- **Shadow System:**
  - **Subtle Surface (`elevation-low`):** `0 1px 3px rgba(11, 25, 44, 0.05), 0 1px 2px rgba(11, 25, 44, 0.03)`
  - **Card Hover / Dropdown (`elevation-mid`):** `0 10px 25px -5px rgba(11, 25, 44, 0.08), 0 4px 6px -2px rgba(11, 25, 44, 0.03)`
  - **Modal / Research Lightbox (`elevation-high`):** `0 25px 50px -12px rgba(11, 25, 44, 0.18)`
- **Border Treatment:** High-resolution 1px hairline borders (`#E2E8F0` or `#CBD5E1`) are used throughout to anchor elements firmly within the 8px grid.

## Shapes
The design uses a restrained, semi-sharp corner radius (Level 1: Soft).

- **Radius Tokens:**
  - `rounded-none`: 0px — Used for table cells, full-bleed hero frames, and split banners.
  - `rounded-sm`: 2px — Small badges, code chips, and technical tags.
  - `rounded` (Default): 4px — Form fields, standard buttons, tabs, and alerts.
  - `rounded-lg`: 8px — Elevated cards, modals, and institutional publication previews.
  - `rounded-xl`: 12px — Dialog containers and primary feature panels.
- Pills and exaggerated curved geometries are strictly prohibited to maintain academic decorum.

## Components

### Buttons
- **Primary:** Deep Chancellor Navy (`#0B192C`) solid background, crisp white typography (`Inter`, 14px, weight 500), 4px border radius. Padding: 10px 20px. Hover: shifts to `#1E3E62`. Focus: 2px offset ring in `#C59B27`.
- **Secondary / Academic Outline:** Pure white or transparent background, 1px border in `#0B192C`, text in `#0B192C`. Hover: fills with `#F8FAFC`.
- **Tertiary / Honorific:** Deep gold outline (`#C59B27`) with gold text. Hover: `#C59B27` background with white text for admissions or endowment CTAs.

### Cards
- **Editorial / Faculty Card:** White background, 1px `#E2E8F0` border, 8px border radius. Padding: 24px. Features an optional 3px top border highlight in `#0B192C` or `#C59B27` to denote program status or honors.
- **Hover State:** Subtly shifts upward by 2px with an increased shadow (`elevation-mid`). Border darkens slightly to `#CBD5E1`.

### Form Inputs & Controls
- **Inputs:** Crisp white background, 1px border `#CBD5E1`, 4px radius, 12px 16px padding. Text: `#0B192C`. Focus state uses a 1px border in `#0B192C` with a soft 2px ambient focus glow (`rgba(11, 25, 44, 0.1)`).
- **Checkboxes & Radios:** Square (4px radius) and circular selectors. Checked fill: `#0B192C` with an ivory tick. Focus: `#C59B27` halo.

### Chips & Badges
- **Academic Taxonomy Tags:** Background `#F1F5F9`, border 1px `#E2E8F0`, text `#1E293B`, uppercase tracking (`0.05em`), 11px font size, 2px radius.
- **Accreditation / Honors Badge:** Background `rgba(197, 155, 39, 0.1)`, text `#8D680E`, border 1px `rgba(197, 155, 39, 0.25)`.

### Lists & Citations
- **Academic Index Lists:** Border-bottom separated entries (`#E2E8F0`), 16px vertical padding, hover background shift to `#F8FAFC`. Serif titles paired with inline sans-serif metadata (DOI, publication year, author initials).

### Global Navigation & Header
- **Navigation Bar:** White surface with bottom border `#E2E8F0`. Primary links in `Inter`, 14px, weight 500, `#1E293B`. Active link denoted by a 2px bottom border indicator in `#C59B27`.