# JobLaunch

A modern job-hunting web UI built with **HTML**, **CSS**, and **Font Awesome**. It includes a hero landing page, category explorer, about section, contact form, and auth flows (login/register) styled with responsive layouts and gradient accents.

## Features
- **Landing (index.html):** Hero search CTA with job-title input and “Find Job” button.
- **Categories (catagories.html):** Grid of role categories with icons and hover effects.
- **About (about.html):** Company story and imagery.
- **Contact (contact.html):** Two-column contact form with styled inputs/textarea.
- **Auth (login.html & register.html):** Seeker/Employer tabs, pills, and form controls with shared theme.
- **Shared styling (style.css):** Dark theme palette, accent colors, responsive grids, and button/input components.

## Getting Started
1. Clone or download the repo.
2. Open `index.html` in your browser to preview locally.
3. Ensure internet access to load Font Awesome and Google Fonts (Sora, Manrope).

## Project Structure
- `index.html` — Landing page.
- `catagories.html` — Category grid.
- `about.html` — About section.
- `contact.html` — Contact form.
- `login.html` — Login screen.
- `register.html` — Seeker/Employer signup.
- `style.css` — Global styles, layout, and responsive rules.
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