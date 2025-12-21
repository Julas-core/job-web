# JobLaunch

A modern job-hunting web UI built with **HTML**, **CSS**, and **Font Awesome**. It includes a hero landing page, category explorer, about section, contact form, and auth flows (login/register) styled with responsive layouts and gradient accents.

## Features
- **Landing (index.html):** Hero search CTA with job-title input and “Find Job” button.
- **Categories (catagories.html / catagories.cata.html):** Grid of role categories and dynamic job listings populated from localStorage seed data.
- **About (about.html):** Company story and imagery.
- **Contact (contact.html):** Two-column contact form with styled inputs/textarea.
- **Auth (login.html & register.html):** Seeker/Employer tabs with inline validation; login supports demo roles.
- **Job detail + apply:** `job-details.html` reads `?id=` to show a job; `apply.html` submits an application (client-side) and blocks duplicates per user/job.
- **Shared styling (style.css) & behavior (script.js):** Dark theme palette, responsive grids, nav drawer, validation, seeded data, and basic localStorage flows.

## Getting Started (demo)
1. Clone or download the repo.
2. Open `index.html` in your browser (or use a simple static server like `npx serve .`).
3. Ensure internet access to load Font Awesome and Google Fonts (Sora, Manrope).

### Demo data
- Seeded accounts: `seeker@test.com / StrongPass!23` (Seeker) and `employer@test.com / StrongPass!23` (Employer).
- Seeded jobs are stored in `localStorage` and rendered on the listings page.

### Flows to try
- Register (seeker/employer) or use seeded accounts → login.
- Browse listings → open a job detail (`job-details.html?id=1`) → Apply (seeker only, duplicate guarded).
- Applications and auth state persist in `localStorage` for the session/device.

## Project Structure
- `index.html` — Landing page.
- `catagories.html` — Category grid.
- `about.html` — About section.
- `contact.html` — Contact form.
- `login.html` — Login screen.
- `register.html` — Seeker/Employer signup.
- `job-details.html` — Job detail page populated via query id.
- `style.css` — Global styles, layout, and responsive rules.
- `script.js` — Nav drawer, inline validation, seeded demo data (localStorage), auth, listings, job detail, and apply flows.
- `assets/` — Images (hero background, students, etc.).

## Customization
- Update accent colors or theme variables in `style.css` under `:root`.
- Replace hero/background images in `assets/`.
- Adjust category cards/icons in `catagories.html`.
- Tweak form fields/placeholders in auth/contact pages.

## Notes
- Responsive breakpoints: 1100px, 800px, 640px, 480px.
- Buttons use shared `.btn`, `.btn-ghost`, `.btn-green` classes with hover shadows.
- Inputs/textareas share consistent padding, borders, and focus outlines.

## License
Add your preferred license here (e.g., MIT).
