# ui-ux-pro-max

UI/UX design intelligence with searchable database. Contains 67 styles, 161 color palettes, 57 font pairings, 99 UX guidelines, and 25 chart types across 16 technology stacks.

## How to Use

When the user requests any UI/UX task (layout, animations, colors, typography, landing page structure), load this skill and follow these steps:

### Step 1: Analyze Requirements
- Product type: SaaS, Micro SaaS, POS, etc.
- Target audience: UMKM Indonesia, small business owners
- Style: dark mode, modern, premium, trust-focused
- Stack: HTML + Tailwind CSS (via CDN) + vanilla JS

### Step 2: Design System Used for Arventa POS
- **Style**: Dark mode SaaS with grey/slate palette, glassmorphism accents
- **Colors**: Dark `#0E0E0E`, Darker `#0A0A0A`, Card `#181818`, Surface `#1E1E1E`, Grey spectrum `#FAFAFA` to `#18181B`
- **Font**: Inter (sans-serif), weights 400-800
- **Effects**: Backdrop blur, animated gradients, glow pulses, card tilt 3D, clip reveals

### Step 3: Animation Enhancements Applied
1. **Lenis smooth scroll** — butter-smooth scrolling with custom cubic easing
2. **Split-text heading** — letter-by-letter reveal with 3D rotation + blur on scroll
3. **Scroll parallax** — hero glow shapes drift at different speeds
4. **Image clip reveal** — POS mockup slides in via clip-path on scroll
5. **Animated counters** — stats count up when scrolled into view (eased cubic-out)
6. **Custom cursor** — dot + ring follower with lag, enlarges on hover over interactive elements
7. **Marquee tech stack** — infinite scrolling "Built With" logos, pauses on hover
8. **Smooth FAQ** — CSS `grid-template-rows` accordion animation
9. **Staggered children** — stat cards animate in sequence with delay

### Step 4: Layout Improvements Applied
1. **Hero social proof** — "50+ UMKM telah menggunakan" with avatar stack
2. **Masalah with red accents** — problem cards use red-tinted icons, emotional urgency
3. **Bento features grid** — "Web Admin Panel" spans 2x2, others wrap in 6-col layout
4. **Cara Kerja connecting lines** — subtle gradient lines between 5 steps on desktop
5. **Testimoni section** — 3 testimonial cards with star ratings, avatars, name + role
6. **Pricing with visible prices** — Rp 99k/bln, Rp 249k/bln, Custom instead of hidden pricing
7. **Trust badges** — business-type chips (Parfume, Bakso, Barber, etc.)
8. **Sticky mobile CTA** — fixed bottom bar with price + "Coba Demo Gratis" button
9. **Contact glow** — radial gradient background, max-width constraint, response time note

### Step 5: Splash Screen
- Full-screen loading overlay with logo animation
- Sequence: logo scales in with bounce → glow rings expand → title fades in → dots pulse → fade out with blur
- Total duration ~4s, respects `prefers-reduced-motion`
- Lenis + all JS features initialize only after splash completes

## File Reference
- `D:\ArventaPOS\landing-page.html` — main landing page (977 lines)

## Available Scripts
```powershell
python3 "$HOME\.agents\skills\ui-ux-pro-max\scripts\search.py" "<keyword>" --domain <domain>
```

### Useful Domains
| Domain | Purpose |
|--------|---------|
| `landing` | Page structure patterns (Hero + Features, Pricing, etc.) |
| `color` | Color palettes by product type |
| `typography` | Google Font pairings |
| `style` | UI styles (glassmorphism, minimal, dark mode) |
| `ux` | Best practices and anti-patterns |
| `product` | Product type recommendations |

## Quick Rules
- 8dp spacing rhythm
- Touch targets >= 44x44pt
- No emoji as icons (use SVG/Heroicons)
- WCAG 4.5:1 text contrast minimum
- Micro-interactions 150-300ms with native easing
