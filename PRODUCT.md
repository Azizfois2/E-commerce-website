# Product

## Register

product

## Users
Systematic buyers seeking technical validation, ranging from budget-conscious builders ("Ultra cheap CN value", X99/Xeon) to high-performance workstation buyers (26,000+ MAD Power Builds). Their context is deeply technical, and their primary job-to-be-done is successfully building a compatible system without the anxiety of hardware bottlenecks or mismatched components.

## Product Purpose
To serve as an intelligent, guided hardware selection tool (PC Builder, Memory Finder, Service selection) that removes the guesswork from PC building. It operates not as a standard e-commerce catalog, but as a high-end engineering application that prioritizes high data density, exact compatibility filtering, and clear system states.

## Brand Personality
"Engineering-grade" and "Glass Cockpit." It is cold, precise, and highly authorized. It communicates the confidence of an advanced hardware diagnostic terminal or developer-focused SaaS. The primary aesthetic relies on deep dark modes, sharp glassmorphism, and highly saturated, singular neon cyan accents to highlight functional data.

## Anti-references
- **Aggressive "Gamer" Tropes:** Absolutely no tribal tattoos, excessive RGB vomit, or hyper-edgy typography.
- **Traditional E-commerce:** Avoid standard retail catalog layouts; the interface should feel like a tool, not a store shelf.

## Design Principles
- **Precision Over Persuasion:** Prioritize exact data, compatibility filtering, and clear system states over marketing fluff.
- **Data Density Without Fatigue:** Organize complex hardware specs through grouping, size, and color (like a modern aviation display) to allow at-a-glance comprehension.
- **Interactive Focus:** Reserve high-saturation accents (neon cyan) strictly for interactive states and critical data visualization, ensuring the interface feels responsive and alive without being overwhelming.

## Accessibility & Inclusion
- **Contrast Target:** Strict WCAG AA (4.5:1) on all Product Surfaces (Builder, forms, tables). Brand Surfaces prioritize text legibility over complex hardware backgrounds.
- **Color Hierarchy:** Body text remains high-contrast off-white/light gray (dark mode) or deep slate/charcoal (light mode). Neon cyan is never used for long-form reading.
- **Opaque Glassmorphism:** Backdrop blurs must be backed by a highly opaque layer (e.g., `rgba(10, 12, 16, 0.85)`) so contrast remains intact even if transparency is disabled.
- **Motion:** Strict adherence to `prefers-reduced-motion`. Micro-animations must be snappy (<300ms); if reduced motion is preferred, animations instantly snap to their final state.
