# VST Translation — Website

Static website (HTML5 + CSS3 + Vanilla JavaScript). No frameworks, no build tools,
no server code. Works immediately when uploaded to HostGator.

## How to publish (HostGator)

1. Open **cPanel → File Manager** (or use FTP).
2. Go into the **`public_html`** folder.
3. Upload **everything inside this folder** (the `index.html`, `css`, `js`,
   `images`, `videos`, `documents` folders) into `public_html`.
   - Tip: zip this folder, upload the zip, then "Extract" inside `public_html`.
4. Visit your domain — done.

> Do **not** upload the outer folder itself; upload its **contents** so that
> `index.html` sits directly inside `public_html`.

## Where to edit things (no advanced coding needed)

| What | Where |
|------|-------|
| Official **logo** | replace `images/logo/vst-logo.svg` (SVG or PNG) |
| **CEO photos** | `images/ceos/dix-aponte.jpg` and `vanusa-da-silva.jpg` |
| **Phones / emails / address / hours** | `js/script.js` → `CONTACT_INFO` |
| **CEO phone numbers** | `js/script.js` → `CEO_PHONES` |
| **Team members** (add/remove) | `js/script.js` → `employees` array |
| **Social media links** | `js/script.js` → `SOCIAL` |
| **Texts (EN / PT / ES)** | `js/script.js` → `translations` |
| **Brand colors** | `css/style.css` → `:root` (top of file) |
| **Tourism photos** | `images/tourism/` (see the note file inside) |
| **Gallery / samples** | `images/gallery/` (see the note file inside) |
| **Presentation video** | `videos/company-presentation.mp4` |
| **QR Code** | `images/qr-code.png` (point it to your final domain) |
| **Google Map** | paste your embed iframe in the Contact section of `index.html` |

## Admin panel (optional — PHP + MySQL)

There is now an optional **admin panel** to manage the team from a login (no code
editing). It needs a HostGator plan with PHP + MySQL. Full instructions:
see **[ADMIN-SETUP.md](ADMIN-SETUP.md)**.

If you don't set it up, nothing breaks — the site keeps using the built-in team
list in `js/script.js`.

## Notes

- Empty fields (a phone/address not yet filled) are **hidden automatically** —
  visitors never see empty placeholders.
- The contact form has **no backend**. By default it opens **WhatsApp** with the
  message ready to send (set the number in `CONTACT_INFO.whatsapp`). If no
  WhatsApp number is set, it opens the visitor's email app instead. You can also
  switch to Formspree/EmailJS — see comments in `js/script.js` (`initForm`).
- **Privacy:** before publishing any sample translation image, blur or remove all
  personal information.

## Current placeholders to replace

- `images/logo/vst-logo.svg` — temporary logo (replace with the official one).
- `images/qr-code.png` — add a QR pointing to your **final** domain.
- Team members, tourism destinations and photos are example placeholders.
