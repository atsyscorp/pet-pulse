---
name: PetPulse
description: Sober, legible clinical interface for veterinary ICU nursing, with a vital-signs trace as its single signature motif.
colors:
  page: "#f1f5f9"
  surface: "#ffffff"
  sunken: "#e8eef5"
  line: "#d5dde8"
  edge: "#738398"
  ink: "#0f172a"
  muted: "#475569"
  accent: "#120c36"
  accent-hover: "#1f1557"
  on-accent: "#ffffff"
  trace: "#0e7490"
  crt: "#6b9a2e"
  ok: "#047857"
  ok-soft: "#d1fae5"
  late: "#b91c1c"
  late-soft: "#fee2e2"
  soon: "#92400e"
  soon-soft: "#fef3c7"
  page-dark: "#120c36"
  surface-dark: "#1b1450"
  sunken-dark: "#0d0928"
  line-dark: "#342b6e"
  edge-dark: "#7a74b4"
  ink-dark: "#f8fafc"
  muted-dark: "#b9b4dc"
  accent-dark: "#22e5f5"
  accent-hover-dark: "#67eef9"
  on-accent-dark: "#120c36"
  trace-dark: "#22e5f5"
  crt-dark: "#a3d95b"
  ok-dark: "#6ee7b7"
  ok-soft-dark: "#064e3b"
  late-dark: "#fca5a5"
  late-soft-dark: "#7f1d1d"
  soon-dark: "#fcd34d"
  soon-soft-dark: "#78350f"
  screen: "#0d0928"
  screen-raised: "#1b1450"
  screen-line: "#342b6e"
  screen-muted: "#b9b4dc"
  screen-ok: "#6ee7b7"
  screen-ok-soft: "#064e3b"
  screen-soon: "#fcd34d"
  screen-soon-soft: "#78350f"
  screen-late: "#fecaca"
  screen-late-soft: "#7f1d1d"
  screen-idle: "#2a2160"
typography:
  display:
    fontFamily: "Outfit Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 5vw, 3.75rem)"
    fontWeight: 800
    lineHeight: 1.1
    letterSpacing: "-0.025em"
  headline:
    fontFamily: "Outfit Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(1.875rem, 3.5vw, 2.25rem)"
    fontWeight: 800
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Outfit Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.33
  body:
    fontFamily: "Outfit Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.625
  label:
    fontFamily: "Outfit Variable, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.33
    letterSpacing: "0.05em"
rounded:
  md: "8px"
  lg: "12px"
  xl: "16px"
  xxl: "24px"
  full: "9999px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "48px"
  section: "80px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.on-accent}"
    rounded: "{rounded.lg}"
    padding: "12px 24px"
    height: "48px"
  button-primary-hover:
    backgroundColor: "{colors.accent-hover}"
  button-primary-dark:
    backgroundColor: "{colors.accent-dark}"
    textColor: "{colors.on-accent-dark}"
    rounded: "{rounded.lg}"
    padding: "12px 24px"
    height: "48px"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: "12px 24px"
    height: "48px"
  button-secondary-hover:
    backgroundColor: "{colors.sunken}"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.xxl}"
  panel-dark:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.ink-dark}"
    rounded: "{rounded.xxl}"
  field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: "12px 16px"
    height: "48px"
  monitor:
    backgroundColor: "{colors.screen}"
    textColor: "{colors.ink-dark}"
    rounded: "{rounded.xxl}"
  theme-toggle:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    rounded: "{rounded.xl}"
    height: "48px"
  cell-ok:
    backgroundColor: "{colors.ok-soft}"
    textColor: "{colors.ok}"
    rounded: "{rounded.lg}"
    height: "40px"
  cell-late:
    backgroundColor: "{colors.late-soft}"
    textColor: "{colors.late}"
    rounded: "{rounded.lg}"
    height: "40px"
  cell-soon:
    backgroundColor: "{colors.soon-soft}"
    textColor: "{colors.soon}"
    rounded: "{rounded.lg}"
    height: "40px"
  cell-pending:
    backgroundColor: "{colors.sunken}"
    textColor: "{colors.muted}"
    rounded: "{rounded.lg}"
    height: "40px"
---

# Design System: PetPulse

## Overview

**Creative North Star: "El Monitor de Signos Vitales"**

PetPulse looks like the calm screen at the head of an ICU bed: a quiet, legible field with one living line running across it. Surfaces are sober and tonal, text is high-contrast, and the pulse trace appears as a deliberate signature (the hero backdrop sweep, the step connector, the login mark) rather than as glow on everything. The one literal instrument on the page is the monitor panel, which stays dark in both themes the way a real device does.

The system has two equal themes, **light** and **dark**, selected by an explicit three-way toggle (light, dark, system). **Light is the default.** The choice is stored in `localStorage` under `petpulse-theme`, wins over the operating-system preference, and an inline script in the document head sets `data-theme` before first paint so there is no flash. Both themes share one hierarchy, one status language and the same 48 px touch targets. All colors are consumed through semantic `--pp-*` custom properties, so components never name a theme.

Density is moderate on the public site: generous 80 px section rhythm, ruled rows instead of card grids. Safety outranks style: anything that could cause a wrong or double dose gets friction and clarity; everything else steps aside.

**Key Characteristics:**
- Light (default) and dark themes with a persistent manual toggle; system is one option.
- Depth from tonal layers and 1 px lines, not shadows.
- Outfit Variable throughout, self-hosted, in Latin American Spanish with full diacritics.
- One signature motif: the vital-signs trace, in CRT phosphor green as the hero backdrop sweep.
- Always-dark monitor device panel carrying real kárdex states, labelled as synthetic.
- Touch-first: 48 px targets, 12 to 24 px radii, status always icon plus label.

## Colors

A cool paper-and-white tonal ramp with night-indigo ink and action in light theme, the logo's indigo night in dark theme, one teal-cyan trace color, one phosphor-green trace color, and soft-filled semantic status colors. All values live as `--pp-*` properties in `resources/css/app.css`; the frontmatter lists light values bare and dark values with a `-dark` suffix.

### Primary
- **Night Indigo** (#120c36, `--pp-accent`): primary action fill, selection, focus ring and wordmark ink in light theme; hover is Indigo Lift (#1f1557). In dark theme this same role is taken by **Pulse Cyan** (#22e5f5, hover #67eef9) with Night text (#120c36), and Night Indigo becomes the page background.

### Secondary
- **Trace Teal** (#0e7490 light / #22e5f5 dark, `--pp-trace`): the "Pulse" half of the wordmark, and link-underline hover. Teal passes contrast on light surfaces; cyan is used only in dark theme.
- **CRT Phosphor** (#6b9a2e light / #a3d95b dark, `--pp-crt`): the single trace color of the system, a yellowish old-monitor green kept well away from status green's blue-green hue. Used for every ECG trace: the hero backdrop sweep (a faint base line at 32% opacity plus the bright live segment), the step connector and the login mark.

### Neutral
- **Page Paper** (#f1f5f9 light / #120c36 dark): page background.
- **Surface** (#ffffff light / #1b1450 dark): panels, fields, secondary buttons, toggle.
- **Sunken** (#e8eef5 light / #0d0928 dark): recessed bands (steps section, footer), progress track, pending cell, button hover.
- **Line** (#d5dde8 light / #342b6e dark): decorative borders and dividers; darker than the earlier draft's #e2e8f0 so hairlines register.
- **Edge** (#738398 light / #7a74b4 dark, `--pp-edge`): the border of form controls (fields, checkboxes), at least 3:1 against their surface so a control's boundary is visible. Never used for decorative hairlines.
- **Ink** (#0f172a light / #f8fafc dark): headings and body.
- **Muted** (#475569 light / #b9b4dc dark): secondary text; darkened from #64748b to hold AA on Page and Sunken.

### Status (kárdex)
Soft fill with a saturated label color, each paired with an icon and a word:
- **Administered**: #047857 on #d1fae5 (dark: #6ee7b7 on #064e3b).
- **Overdue**: #b91c1c on #fee2e2 (dark: #fca5a5 on #7f1d1d).
- **Due soon**: #92400e on #fef3c7 (dark: #fcd34d on #78350f).
- **Pending**: Muted on Sunken, no color alert.

### Monitor screen (always dark, both themes)
`screen` #0d0928, `screen-raised` #1b1450, `screen-line` #342b6e, `screen-muted` #b9b4dc, `screen-ok` #6ee7b7 on #064e3b, `screen-soon` #fcd34d on #78350f, `screen-late` #fecaca on #7f1d1d, `screen-idle` #2a2160. Text on it is #f8fafc.

### Named Rules
**The Trace-Only Cyan Rule.** Cyan (#22e5f5) is a dark-theme action fill and accent, never a paragraph color on a light surface; light theme uses Trace Teal.

**The Status Is Sacred Rule.** Green, red and amber mean dose state and nothing else. No decorative use anywhere. Traces use CRT Phosphor, a separate yellower token, and are not a status color.

**The Dark Device Rule.** The monitor panel keeps its `screen-*` tokens in both themes. It is a device, not a surface that themes.

*Resolved:* CRT Phosphor was moved to a yellower hue (about 90°) so it no longer reads as the status green (about 160°), and all traces now share it, so the motif has one color.

## Typography

**Display Font:** Outfit Variable (with `ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif` fallback), self-hosted through `@fontsource-variable/outfit`
**Body Font:** Outfit Variable (same family)
**Label/Mono Font:** Outfit; `tabular-nums` (utility `pp-num`) for doses, times and stock.

**Character:** A geometric, friendly sans that stays precise at small sizes. Weight and size carry hierarchy, not a second family.

### Hierarchy
- **Display** (800, 36 to 60 px, 1.1, -0.025em): hero headline only; `text-balance`.
- **Headline** (800, 30 to 36 px, tight, -0.025em): section headings and the contact panel.
- **Title** (700, 24 px; 20 px for steps and audience cards): feature row and step headings.
- **Body** (400, 18 px, 1.625): running text in muted ink; supporting copy caps near `max-w-xl`/`max-w-sm`.
- **Label** (700, 12 px, uppercase, +0.05em): table column headers in the traceability specimen only. Not for eyebrows above headings, and not for sentences.

### Named Rules
**The Numbers Align Rule.** Every dose, time, stock figure and step number uses tabular numerals so columns read at a glance.

## Layout

Content sits in a centered container up to 1280 px (`max-w-7xl`) with 16 px gutters on phones and 24 px from 640 px up. Sections use 80 px vertical padding separated by 1 px lines; the steps section and footer sit on the Sunken band. The hero is a split grid (1.05fr / 1fr) from 1024 px, with the monitor on the right and 160 to 176 px of bottom padding reserved for the backdrop trace. Features are four **ruled rows**: a 5/7 column split (copy, then an in-page specimen) divided by 1 px rules, never a card grid. Steps are a three-column list joined by a trace line on desktop and a vertical 1 px rule on phones. The audience block is three columns topped by a 2 px Ink rule. The header is sticky and 64 px tall; below 1024 px it collapses to a 48 px menu button and a drawer that also holds the theme toggle. Spacing follows a 4 px base (4, 8, 16, 24, 48, 80).

## Elevation & Depth

Tonal layers and lines, no shadows. Depth is stepping between Page and Surface (light) or Page and Raised Night (dark), separated by a 1 px Line border; Sunken bands recess. The sticky header is Page at 95% opacity with a small `backdrop-blur`, a functional translucency, not a lift. There are no box-shadows on any element.

### Named Rules
**The Flat-By-Default Rule.** Surfaces are flat at rest. Hover and focus change tone and border, never blur or shadow.

**The Single Glow Rule.** The hero ECG live segment carries one soft `drop-shadow(0 0 5px var(--pp-crt))`, the phosphor bloom of an old monitor. This is the sole sanctioned glow in the system, applies to that trace only, and no other element, text, button or panel glows.

## Shapes

Large, friendly corners that read as tappable: 12 px for buttons, fields, toggle buttons and kárdex cells; 16 px for the toggle container, menu button, logo tile and step numerals; 24 px for panels and the monitor; full-round for status dots, pills and the progress bar. Borders are 1 px Line. The only decorative geometry is the ECG polyline (round caps and joins) and the drawn specimens.

## Components

### Buttons
- **Shape:** 12 px radius, 48 px minimum height, 24 px horizontal padding, 700 weight, 16 px text.
- **Primary:** Night Indigo fill and white text (light); Pulse Cyan fill and Night text (dark). Hover shifts to the lift tone; press scales to 98%. Header buttons shrink to `py-2` and 14 px text but keep 48 px height.
- **Secondary:** Surface fill, 1 px Line border, Ink text; hover fills Sunken.
- **Focus:** 2 px outline in `--pp-ring` (Night Indigo light, Cyan dark), offset 3 px, on every focusable element.

### Panels
- 24 px radius, Surface fill, 1 px Line border, no shadow. Padding 20 px for specimens, 32 to 48 px for the contact and login panels.

### Fields
- 12 px radius, 48 px minimum height, 1 px Line border, Surface fill, 16 px text; focus shifts the border to Ink and shows the global focus ring. Labels sit above in 14 px semibold Ink and stay visible. Errors turn the border to the late color and add a message in bold late-colored text with `role="alert"`, never color alone. Placeholder is Muted at 70%.

### Quiet link
- Ink, semibold, underlined with a 2 px Line-colored decoration and 4 px offset; the decoration turns Trace on hover.

### Theme toggle
- A grouped control of three 48 px square buttons (sun, moon, monitor icons, 20 px) in a 1 px Line container with 3 px padding and 16 px outer radius. The active preference fills with the accent and `on-accent`. Lives in the desktop header and in the mobile drawer.

### Monitor (signature)
- A 24 px figure on the `screen` palette with a Screen Line header ("UCI · Sala 1" and a sync dot), patient line, and three dose rows (icon tile, tabular time, drug, volume, state word). Dose-state tiles use the `screen-*` soft fills. A caption states the data is fictitious.

### Kárdex cell
- 40 px high, 12 px radius, 12 px bold text, centered 16 px icon. States: **ok** (check), **late** (exclamation), **soon** (clock), **pending** (dash on Sunken). Fill, icon and word (in the legend) always travel together. Legend swatches reuse the cell at 24 px.

### Ruled feature rows with specimens
- Each feature pairs copy with a drawn, real-looking specimen inside a Panel: a kárdex grid (3 patients by 6 hours), an inventory readout with a progress bar and note, a ledger table with tabular figures, and a three-clinic comparison. Specimens use synthetic data and say so.

### Steps with trace line
- Numbered 48 px tiles (16 px radius, Surface, 1 px Line) under a CRT Phosphor ECG polyline on desktop, a vertical 1 px Line on phones.

### Navigation
- Sticky 64 px header on Page at 95%, bottom 1 px Line. Logo tile (40 px, 12 px radius) with wordmark "PetPulse" in one word, "Pulse" in Trace. Links are 14 px semibold Muted with Ink hover and 48 px hit height.

### Hero ECG sweep (signature)
- A full-width SVG heartbeat polyline behind the hero, masked to fade at both edges. A base trace at 32% opacity plus a live segment (dash 260 of 1000) sweeping on a 3 s linear loop, both CRT Phosphor. Decorative and `aria-hidden`. Under `prefers-reduced-motion` the sweep stops and the live segment renders as a full static line.

## Do's and Don'ts

### Do:
- **Do** default to light, offer light, dark and system, store the explicit choice under `petpulse-theme`, and apply it before first paint.
- **Do** consume color only through the `--pp-*` semantic tokens so both themes work.
- **Do** keep every interactive target at least 48 px and every text pair at WCAG AA.
- **Do** pair every status color with an icon or a word.
- **Do** keep the monitor on its always-dark `screen-*` tokens in both themes.
- **Do** use ruled rows and drawn specimens with labelled synthetic data to show product states.
- **Do** keep Outfit self-hosted and honor `prefers-reduced-motion`: the hero trace becomes a static line.

### Don't:
- **Don't** add glow to anything other than the hero ECG live trace; no neon text-shadow, blurred orbs, or box-shadow depth.
- **Don't** put Pulse Cyan text on a light surface; use Trace Teal.
- **Don't** use green, red or amber decoratively, and don't use CRT Phosphor for anything but ECG traces.
- **Don't** animate inside the kárdex board beyond the overdue dot.
- **Don't** replace ruled feature rows with an icon-card grid.
- **Don't** invent customers, testimonials or metrics in marketing sections.
