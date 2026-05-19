---
name: Maroc PC
description: High-end engineering platform and hardware diagnostic terminal.
colors:
  primary: "#00f5d4"
  primary-light: "#007A6E"
  primary-dim: "#00c4aa"
  primary-dim-light: "#005E55"
  secondary: "#ff6b35"
  secondary-light: "#D95F0A"
  neutral-bg: "#050505"
  neutral-bg-light: "#EAECF0"
  neutral-bg-2: "#0a0b0e"
  neutral-bg-2-light: "#F2F4F7"
  neutral-bg-3: "#0d0f13"
  neutral-bg-3-light: "#F8F9FB"
  neutral-bg-4-light: "#FFFFFF"
  neutral-text: "#b0b8c8"
  neutral-text-light: "#334155"
  neutral-muted: "#5a6170"
  neutral-muted-light: "#64748B"
  white: "#eef0f4"
  white-light: "#0F172A"
  diagnostic-green: "#00e676"
  diagnostic-green-light: "#00a152"
  diagnostic-red: "#ff4444"
  diagnostic-red-light: "#d32f2f"
typography:
  display:
    fontFamily: "'Orbitron', monospace"
  body:
    fontFamily: "'Syne', sans-serif"
  label:
    fontFamily: "'JetBrains Mono', monospace"
rounded:
  sm: "6px"
  md: "8px"
  lg: "14px"
spacing:
  sm: "8px"
  md: "12px"
  lg: "24px"
components:
  btn-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#000000"
    rounded: "{rounded.sm}"
    padding: "12px 24px"
  btn-outline:
    backgroundColor: "transparent"
    textColor: "{colors.primary}"
    rounded: "{rounded.md}"
    padding: "14px 36px"
  stat-card:
    backgroundColor: "rgba(0, 0, 0, 0.55)"
    textColor: "{colors.white}"
    rounded: "{rounded.lg}"
    padding: "22px 18px"
---

# Design System: Maroc PC

## 1. Overview

**Creative North Star: "The Glass Cockpit"**

Cold, precise, and data-dense. Surfaces are dark and layered, relying on stark contrast and sharp glassmorphism to prioritize compatibility data over marketing persuasion. The interface must explicitly prioritize structural precision and act as a high-stakes modern aviation display or advanced diagnostic terminal. It explicitly rejects aggressive "Gamer" tropes, tribal tattoos, excessive RGB vomit, and traditional e-commerce catalog layouts.

**Key Characteristics:**
- High-tolerance structural precision
- Data density over marketing fluff
- Glassmorphism backed by deep opaque layers
- Neon accents restricted purely to interactive states

## 2. Colors

A strictly controlled diagnostic palette that adapts between "Deep Space" (Dark Mode) and "Clean Room" (Light Mode), always emphasizing functional data over decoration.

### Primary
- **Terminal Cyan** (Dark: `#00f5d4` / Light: `#007A6E`): Reserved strictly for interactive states (hover, focus, active selection) and critical data visualization. Never used for long-form reading.

### Secondary
- **Thermal Orange** (Dark: `#ff6b35` / Light: `#D95F0A`): Used for alerts, warnings, and highlighting high-tier power performance or stress testing context.

### Neutral
- **Structural Base** (Dark: `#050505` / Light: `#EAECF0`): The primary structural background, providing immense contrast.
- **Glass Backing** (Dark: `rgba(5, 5, 5, 0.75)` / Light: `rgba(242, 244, 247, 0.94)`): Opaque base for backdrop filters to ensure reliable contrast.
- **Diagnostics High-Contrast** (Dark: `#eef0f4` / Light: `#0F172A`): High-contrast text for critical headings.
- **Data Body** (Dark: `#b0b8c8` / Light: `#334155`): Main body copy, avoiding pure extreme tones to reduce eye fatigue.

### Status Colors
- **Diagnostic Green** (Dark: `#00e676` / Light: `#00a152`): System nominal, safe headroom, compatibility confirmed.
- **Diagnostic Red** (Dark: `#ff4444` / Light: `#d32f2f`): Incompatible component, critical wattage exceeded.

### Named Rules
**The Strict Interaction Rule.** Terminal Cyan is never used for decorative blocks or long-form body text. Its presence is exclusively a signal that an element is interactive or requires immediate diagnostic attention.

## 3. Typography

**Display Font:** 'Orbitron', monospace
**Body Font:** 'Syne', sans-serif
**Label/Mono Font:** 'JetBrains Mono', monospace

**Character:** Highly technical and mechanical, blending structural engineering sans-serifs with diagnostic monospaced readouts.

### Hierarchy
- **Display** (800, clamp(2.4em, 4vw, 4em)): Hero sections and major status indicators.
- **Headline** (700, 1.5rem): Card headers and primary groupings.
- **Body** (400, 1rem, 1.6): System descriptions and general text.
- **Label** (700, 0.65rem, 2px letter-spacing, uppercase): Spec keys, component tags, and micro-data.

### Named Rules
**The Readout Rule.** System specifications, data keys, and numeric metrics must use the monospace label font to align vertically and convey machine-level precision.

## 4. Elevation

Flat at rest, lifted and glowing on interaction.

### Shadow Vocabulary
- **Interactive Lift** (`0 12px 28px rgba(0,0,0,0.45)`): Applied only when an element is hovered or focused.
- **Diagnostic Glow** (`0 0 16px rgba(0, 245, 212, 0.15)`): Terminal Cyan glow applied underneath lifted interactive elements to confirm user engagement.

### Named Rules
**The Flat-By-Default Rule.** The base application remains flat to keep it clean, high-contrast, and performant. Shadows and depth changes are reserved purely to confirm user engagement.

## 5. Components

Sharp, structured, and industrial. Elements feel like high-tolerance mechanical components.

### Buttons
- **Shape:** Strict borders, slight industrial rounding (6px - 8px). Zero decorative softness.
- **Primary:** Terminal Cyan background with pure black text for maximum contrast.
- **Hover / Focus:** Lifts via Y-axis translation, triggering the Diagnostic Glow shadow.

### Stat Cards
- **Corner Style:** 14px radius.
- **Background:** Opaque dark backing (`rgba(0, 0, 0, 0.55)`) combined with backdrop blur.
- **Border:** 1px solid semi-transparent Terminal Cyan to define structural edges.
- **Hover:** Deepens the shadow, shifts the border to pure Terminal Cyan, and scales icons mechanically.

### Inputs / Fields
- **Style:** Flat dark background (`#13151a`) with a subtle cyan-tinted border (`rgba(0, 245, 212, 0.2)`).
- **Focus:** Sharp transition to full Terminal Cyan border and subtle cyan glow.

## 6. Do's and Don'ts

### Do:
- **Do** rely on grouping, size, and color (aviation display style) to allow at-a-glance comprehension.
- **Do** use strict WCAG AA contrast ratios (4.5:1) across all Product Surfaces (PC Builder, forms, tables).
- **Do** back any glassmorphism blurs with highly opaque layers (e.g., `rgba(10, 12, 16, 0.85)`) to preserve structural legibility.
- **Do** adhere strictly to `prefers-reduced-motion` and keep micro-animations snappy (under 300ms).

### Don't:
- **Don't** use aggressive "Gamer" tropes, tribal tattoos, or excessive RGB vomit.
- **Don't** use traditional e-commerce catalog layouts; the interface must feel like an engineering tool.
- **Don't** use Terminal Cyan for long-form reading or passive decorative backgrounds.
- **Don't** use pure black or pure white for body text.
- **Don't** rely purely on backdrop blur for contrast; always provide a strong background color.
