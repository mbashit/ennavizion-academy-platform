# Ennavizion.com — youth football academy website

A complete static website: plain HTML, CSS and JavaScript. No build step, no frameworks.

## Open it in VS Code

1. Unzip the folder and open it in VS Code (**File → Open Folder…**).
2. Install the **Live Server** extension (by Ritwick Dey).
3. Right-click `index.html` → **Open with Live Server**. The site opens in your browser and reloads when you save.

(You can also just double-click `index.html`, but Live Server is better.)

## Pages

| File | What it is |
|---|---|
| `index.html` | Home: hero, about teaser, player pathway, league snapshot, testimonial, latest posts |
| `about.html` | Story, values, coaching staff, FAQ |
| `programs.html` | Programs and fees, training approach, centres, other services |
| `league.html` | League table, fixtures and results (tabs) |
| `gallery.html` | Photo grid with filters and a lightbox |
| `shop.html` | Products, sizes, cart drawer, order via WhatsApp |
| `blog.html` / `post.html` | Blog list with search and categories; single post page |
| `enroll.html` | 3-step player enrolment form with validation and review |
| `contact.html` | Contact details, map and contact form |
| `admin.html` | Test-mode viewer for form submissions (CSV export) |
| `404.html` | Not-found page |

Header, footer, cart and the WhatsApp button are added to every page by `js/main.js`, so you only edit them in one place.

## Where to change things

- **Contact details, WhatsApp number, currency, social links:** top of `js/data.js` (`SITE`).
- **Programs and fees, centres, league table/fixtures/results, gallery, products, blog posts:** also in `js/data.js`. The league table sorts itself and calculates points.
- **Page text:** the `.html` files.
- **Colours and fonts:** the `:root` block at the top of `css/style.css`.
- **Map:** the `<iframe>` in `contact.html` — replace the address in the `q=` part of the URL.

## Photos

Put your photos in `assets/images/`. Until a photo exists, a green placeholder shows instead.

- Fixed slots in the HTML use these names: `about.jpg`, `story.jpg`, `services.jpg`, `coach-1.jpg` … `coach-4.jpg`.
- Gallery, products and blog posts: set `src` (gallery) or `image` (products, posts) in `js/data.js`, e.g. `image: "assets/images/home-kit.jpg"`.

Resize photos to about 1600px wide and compress them (e.g. squoosh.app) so pages load fast.

## Making the forms send emails

By default forms run in **demo mode**: submissions are saved in your browser and visible at `admin.html`.

To receive them by email:
1. Create a free form at https://formspree.io and copy its endpoint (looks like `https://formspree.io/f/abcdwxyz`).
2. Paste it into `formEndpoint` in `js/data.js`.

Contact, enrolment and newsletter forms will then all post there.

## Shop payments

The cart sends the order to your WhatsApp with items, sizes and total, and you confirm payment with the customer. To take card payments on the site, add Paystack or Flutterwave checkout to the `#checkout-btn` handler in `js/main.js`.

## Going live on ennavizion.com

Any static host works. Easiest options:
- **Netlify:** drag the whole folder onto app.netlify.com/drop, then add `ennavizion.com` under Domain settings and update DNS at your registrar.
- **Vercel** or **GitHub Pages** work the same way.
- **cPanel hosting:** upload everything into `public_html`.

Set `404.html` as the not-found page on your host.
