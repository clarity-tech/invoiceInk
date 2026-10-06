# Changelog

## [1.4.0](https://github.com/clarity-tech/invoicing/compare/v1.3.5...v1.4.0) (2026-10-06)


### Features

* **deploy:** add an MCP staging environment on Cloud Run ([5713a33](https://github.com/clarity-tech/invoicing/commit/5713a33a432ef646add7076f206755c68c499efb))
* **mcp:** expose invoicing data as a read-only MCP server ([97bcf28](https://github.com/clarity-tech/invoicing/commit/97bcf282df0fa9fbcaccfa165588406f559c3d7b))


### Build & CI

* cut CI from six jobs to three and bound every job ([bd80de5](https://github.com/clarity-tech/invoicing/commit/bd80de51009637d9408cafd1c68ae7b8cfd37ad6))

## [1.3.5](https://github.com/clarity-tech/invoicing/compare/v1.3.4...v1.3.5) (2026-08-20)


### Build & CI

* build the octane test image from the shared layer cache ([0e2541a](https://github.com/clarity-tech/invoicing/commit/0e2541a3edac195db9871a35c8346811690797b3))
* stop building the same image twice per pull request ([b8fee43](https://github.com/clarity-tech/invoicing/commit/b8fee4351158de08c855d760c527f34207df23e3))

## [1.3.4](https://github.com/clarity-tech/invoicing/compare/v1.3.3...v1.3.4) (2026-08-19)


### Bug Fixes

* **dashboard:** disclose currency conversion and label the all-time figures ([84577f1](https://github.com/clarity-tech/invoicing/commit/84577f1bb3f1286e68a1c56d69e313c66244268a))

## [1.3.3](https://github.com/clarity-tech/invoicing/compare/v1.3.2...v1.3.3) (2026-08-19)


### Bug Fixes

* **customers:** open the location step the create flow promises ([2ca4b6b](https://github.com/clarity-tech/invoicing/commit/2ca4b6b2101afd1ac63a43662c253d61cc27acb5))
* **invoices:** surface validation errors when the customer has no location ([f457b7a](https://github.com/clarity-tech/invoicing/commit/f457b7ac1339637cfde653a00d709863faeda972))

## [1.3.2](https://github.com/clarity-tech/invoicing/compare/v1.3.1...v1.3.2) (2026-08-19)


### Bug Fixes

* **email:** record document email outcomes instead of assuming success ([1341c6c](https://github.com/clarity-tech/invoicing/commit/1341c6c91219f6adfa2fec2b9d8efe9cbed477c1))

## [1.3.1](https://github.com/clarity-tech/invoicing/compare/v1.3.0...v1.3.1) (2026-08-13)


### Bug Fixes

* **customers:** clear the search box when the server reports no active search ([2c4a32f](https://github.com/clarity-tech/invoicing/commit/2c4a32fa2b698d841915ca99c006807cca5d3bf3))

## [1.3.0](https://github.com/clarity-tech/invoicing/compare/v1.2.0...v1.3.0) (2026-08-13)


### Features

* **customers:** add a customer detail page that owns location management ([b1c3b56](https://github.com/clarity-tech/invoicing/commit/b1c3b564e19dbf778065967a71bd3a0de4de868e))


### Bug Fixes

* **customers:** make edit and location management reachable in the listing ([c229618](https://github.com/clarity-tech/invoicing/commit/c229618ad9896b7c3a57421255a5a04245ffdc76))

## [1.2.0](https://github.com/clarity-tech/invoicing/compare/v1.1.3...v1.2.0) (2026-08-12)


### Features

* **a11y:** announce flash outcomes and trap focus in ConfirmationModal ([e9bd8b5](https://github.com/clarity-tech/invoicing/commit/e9bd8b54bd002ba62c01c2b0be6bc51f5f3fdee5)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)
* **invoices:** give the listing accessible names and table semantics ([90b2616](https://github.com/clarity-tech/invoicing/commit/90b26160c84bb753924597c5e5fdb0134e01810c)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)


### Bug Fixes

* **invoices:** hand focus to the list when a delete removes the focused row ([806464e](https://github.com/clarity-tech/invoicing/commit/806464e2d9b3ec2d7c3bf42d37ba7d49516f8f67)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)
* **invoices:** make pagination real links with a current-page marker ([8016272](https://github.com/clarity-tech/invoicing/commit/801627240ba8b3eea722a717cfc0860337952d24)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)
* **invoices:** raise listing text contrast to meet WCAG AA ([4e6dba4](https://github.com/clarity-tech/invoicing/commit/4e6dba4acffb2eea54f18ef1cea451b1373e9e0e)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)
* **invoices:** stop the listing header and filters overflowing on mobile ([19eee4b](https://github.com/clarity-tech/invoicing/commit/19eee4b710187619dea885e2d962f19376b1280e)), closes [#33](https://github.com/clarity-tech/invoicing/issues/33)

## [1.1.3](https://github.com/clarity-tech/invoicing/compare/v1.1.2...v1.1.3) (2026-08-12)


### Bug Fixes

* **invoices:** duplicate opens an unsaved copy instead of creating one ([0a32e53](https://github.com/clarity-tech/invoicing/commit/0a32e5343588ee50da8b7d70e781d5622638ce99))
* **urls:** pin the URL origin only when running on Cloud Run ([a3a0e48](https://github.com/clarity-tech/invoicing/commit/a3a0e483956391c5b21e7764abfc37b7a89b0cb2))

## [1.1.2](https://github.com/clarity-tech/invoicing/compare/v1.1.1...v1.1.2) (2026-08-11)


### Bug Fixes

* **emails:** remove duplicate "View Online" CTA from document emails ([e663826](https://github.com/clarity-tech/invoicing/commit/e663826ef2d6187fce61b754df9d687bfaf18183))
* **urls:** root emailed links at the public domain, not the request host ([2fed58b](https://github.com/clarity-tech/invoicing/commit/2fed58bf1863e5145e536becd6c30800dc9dc6af))

## [1.1.1](https://github.com/clarity-tech/invoicing/compare/v1.1.0...v1.1.1) (2026-08-11)


### Bug Fixes

* stop repeating the state when it echoes the city in customer locations ([882a013](https://github.com/clarity-tech/invoicing/commit/882a0133f5918c272e56572e5e32f91571074ca8))


### Build & CI

* report Trivy findings in the run summary instead of the Security tab ([f4e3068](https://github.com/clarity-tech/invoicing/commit/f4e3068d97ee69aa35cd467ac8fc4754380f87ee))
* report Trivy findings in the run summary instead of the Security tab ([7237ad0](https://github.com/clarity-tech/invoicing/commit/7237ad0ce69496f3e6c4a52304fcd4847fe97729))

## [1.1.0](https://github.com/clarity-tech/invoicing/compare/v1.0.0...v1.1.0) (2026-08-04)


### Features

* Achieve 100% Dusk test pass rate and add composer script for test management ([2a45c00](https://github.com/clarity-tech/invoicing/commit/2a45c00f684e29eba83acb4369b1c5aa0bd95dd2))
* add "Same as billing address" toggle on invoice/estimate form ([c669c10](https://github.com/clarity-tech/invoicing/commit/c669c10b0c5646c2c25d5174ec5c75a06d5f0e57))
* add a helper for running artisan commands on Cloud Run ([7d16ef6](https://github.com/clarity-tech/invoicing/commit/7d16ef665ba14dd41e4186e76234f70cd69c37ab))
* add AED currency support with symbol and name ([677248c](https://github.com/clarity-tech/invoicing/commit/677248c9141c6492a2626589542ce83705a64716))
* add analytics dashboard with KPI cards, trends, and status breakdown ([3697283](https://github.com/clarity-tech/invoicing/commit/36972830687d251c0db8ade74443192724b6a9bc))
* add authorization policies for numbering series and tax templates ([e0a523f](https://github.com/clarity-tech/invoicing/commit/e0a523fd3bc6c1a713b79839d68a09a50a95983e))
* add BIMI logo (SVG Tiny P/S) for email brand indicator ([5b00764](https://github.com/clarity-tech/invoicing/commit/5b00764c6399642b439ca8c0c6c3efcf697d976e))
* add Cloud Run service manifests and deploy tooling ([2a75229](https://github.com/clarity-tech/invoicing/commit/2a75229228249e1469570a993a750079cf8770dd))
* add CompanyManager Livewire component with CRUD operations ([8cc370c](https://github.com/clarity-tech/invoicing/commit/8cc370c74ea00a68dc73d8a986dad322f6db5809))
* add comprehensive database seeders for multitenant invoicing demo ([f819f5a](https://github.com/clarity-tech/invoicing/commit/f819f5a2205521c2ac3abc93bd85a08c48e149cc))
* add comprehensive edge case and additional model tests ([d1d0329](https://github.com/clarity-tech/invoicing/commit/d1d0329d40e1ae7744e92ebf51e83a0ef43bbe25))
* add comprehensive Livewire component tests ([1e4a5c3](https://github.com/clarity-tech/invoicing/commit/1e4a5c3e0300f298d33dd6bfb6e0e063214beda4))
* add comprehensive PDF export functionality ([b3c89d2](https://github.com/clarity-tech/invoicing/commit/b3c89d23990c4d8005f92d8e3100ca70a0311e41))
* add comprehensive test coverage for 90% target ([2dcc631](https://github.com/clarity-tech/invoicing/commit/2dcc631a499ab84ec415b03db95b9496165ffff3))
* add comprehensive test coverage for low-coverage components ([885f401](https://github.com/clarity-tech/invoicing/commit/885f401a63538d894a28818404d6c553f403e0b2))
* add core financial year infrastructure ([91535da](https://github.com/clarity-tech/invoicing/commit/91535da520a88fc3395b011a78e5e4b042b079f2))
* add custom delete confirmation dialog for recorded payments and line items ([78630d7](https://github.com/clarity-tech/invoicing/commit/78630d7ef04bad25d3bc326583b60e8b7e8abe0b))
* add CustomerManager Livewire component with CRUD operations ([0a16cd6](https://github.com/clarity-tech/invoicing/commit/0a16cd6343369b09f5bc879ceb834809aea19242))
* add DocumentMailer mailable class for sending invoices and estimates ([52e5c1b](https://github.com/clarity-tech/invoicing/commit/52e5c1b8d2a009aa2c0dfa4fde5d83145bff308f))
* add email template backend — model, enum, defaults, service ([f937e5a](https://github.com/clarity-tech/invoicing/commit/f937e5abcc1fe1ae280a4d90653a293f2310069f))
* add email template management UI with TipTap rich text editor ([f937e5a](https://github.com/clarity-tech/invoicing/commit/f937e5abcc1fe1ae280a4d90653a293f2310069f))
* add EmailCollection Value Object for managing email arrays ([4069290](https://github.com/clarity-tech/invoicing/commit/40692909ef5569877161ba2ca78b8d7086a97461))
* add EmailCollectionCast and update Company/Customer models to use it ([4069290](https://github.com/clarity-tech/invoicing/commit/40692909ef5569877161ba2ca78b8d7086a97461))
* add ESLint/Prettier, clean up Tailwind v4 and Vite config ([8d4168e](https://github.com/clarity-tech/invoicing/commit/8d4168e34c3882d72da2082012fb42a9a0a4cee8))
* add EstimateToInvoiceConverter service for converting estimates to invoices ([bc12d1f](https://github.com/clarity-tech/invoicing/commit/bc12d1f491ce1ab8c8f8957a64ca2ee5c565ef41))
* add fillable attributes to Membership and TeamInvitation models ([0539b25](https://github.com/clarity-tech/invoicing/commit/0539b25607ed6054b3794365eb796735d6a0fa03))
* add financial year validation during invoice creation ([9b2aa33](https://github.com/clarity-tech/invoicing/commit/9b2aa33dd26a5c2fb2f45c7c868e91659a757379))
* add GST treatment to gate place of supply for overseas customers ([83b2962](https://github.com/clarity-tech/invoicing/commit/83b29620a48509b5da8a6193c55653192b1f3d63))
* add Indian number grouping (lakh/crore) for INR currency ([ba5d075](https://github.com/clarity-tech/invoicing/commit/ba5d075bd6174934e08f4cbe1482db9b100a386a))
* add Inertia 2 + Vue 3 + TypeScript foundation with dual-stack support ([3511413](https://github.com/clarity-tech/invoicing/commit/3511413ae901d5030b415763ba539d3d75dc1afa))
* add Inertia v3 optimistic deletes for invoices and customers ([380e3af](https://github.com/clarity-tech/invoicing/commit/380e3af69a2abd0882a84a59b5d35ca9fd85f3e0))
* add invoice payment tracking system ([ed7cacd](https://github.com/clarity-tech/invoicing/commit/ed7cacd0f6a0b4e448a83a5010da775d5a6629d7))
* add InvoiceCalculator service for calculating invoice totals ([beb8e4d](https://github.com/clarity-tech/invoicing/commit/beb8e4d7dfde5fb4060e14328e1af39b1a1080bc))
* add InvoiceWizard unified component for creating invoices and estimates ([03f421e](https://github.com/clarity-tech/invoicing/commit/03f421e242e42f671b3540ac8693e9bbb643d65d))
* add models and migrations for invoicing system ([2a802d2](https://github.com/clarity-tech/invoicing/commit/2a802d24bd946397f0d879fd13ead7b9e7098bcd))
* add multi-currency cycling hero section with official UAE dirham symbol ([b284510](https://github.com/clarity-tech/invoicing/commit/b2845104ebb71127ab08799d0092ba3dc42bf545))
* add null checking and error handling to TeamSettings component ([d380b4f](https://github.com/clarity-tech/invoicing/commit/d380b4ff2fcb6e4d38d6cd825ea80ad0cbc30f9f))
* add numbering series management interface ([86c3117](https://github.com/clarity-tech/invoicing/commit/86c3117d5a859cba4dbf0c378b906026c8ba6483))
* add order number field to invoices ([f62b8b8](https://github.com/clarity-tech/invoicing/commit/f62b8b842b104e9db7a4d26997b41c2dc5b09b3d))
* add payment recording UI with modal, history, and auto status ([98c3a3f](https://github.com/clarity-tech/invoicing/commit/98c3a3ffed193386dc3095fda948172f71a87484))
* add payment terms field to invoice/estimate form ([375aa5a](https://github.com/clarity-tech/invoicing/commit/375aa5a85bbca9c84fd87ead30f479ff9d26d173))
* add payment terms, ship-to section, and org contact to invoice template ([32a8562](https://github.com/clarity-tech/invoicing/commit/32a856202699c9eda30767ad6d8603c05533c7a2))
* add place of supply field to customers and invoices ([92c7061](https://github.com/clarity-tech/invoicing/commit/92c70611edf293e22b90885811d4087c76bc8bb0))
* add product demo screenshot capture via Pest Browser ([a76a174](https://github.com/clarity-tech/invoicing/commit/a76a174949355b1b033ce6f5f589146c2f2441f6))
* add public view routes and complete application structure ([9cee251](https://github.com/clarity-tech/invoicing/commit/9cee251cc477e475bde1865ab7fa94f4f2e7ee2a))
* add register page link in login page ([77b13cf](https://github.com/clarity-tech/invoicing/commit/77b13cf3719d56aeb0dbc9ceb31324c01b09fe7e))
* add Save and Save & Send actions to the invoice form ([caa0ca4](https://github.com/clarity-tech/invoicing/commit/caa0ca4bba3b34e28305156d98ef8e5526d2f863))
* add service interfaces, team UX improvements, and ULID route validation ([2c88cf1](https://github.com/clarity-tech/invoicing/commit/2c88cf17d4062efe519982816e688e8d0e791b18))
* add Settings hub with Overview tab ([ff4bc5c](https://github.com/clarity-tech/invoicing/commit/ff4bc5ca704f0e476e3a67ca8493857578569813))
* add settings sub-nav and email templates smoke test ([b93592a](https://github.com/clarity-tech/invoicing/commit/b93592af7113f440a8a6cf0c0eab778e359128f2))
* add Settings/Index page and SettingsController ([5acf8a8](https://github.com/clarity-tech/invoicing/commit/5acf8a858de380daf586ed48b6d9a8aaa11b7206))
* add Sprint 2 improvements — security, UX, features, accessibility ([9d34956](https://github.com/clarity-tech/invoicing/commit/9d349563cd8090e8fcceb4f76ea6c5cb5378d663))
* add Sprint 3 — model scopes, policies, performance, accessibility ([226234e](https://github.com/clarity-tech/invoicing/commit/226234e1bee79dddadcc73374b67ad024e9e4a72))
* add status dropdown for invoice/estimate updates ([fe4d1ab](https://github.com/clarity-tech/invoicing/commit/fe4d1ab7ab6c9b72b56f65973418ba7851dddaa6))
* add subject field and invoice helper methods ([9c78241](https://github.com/clarity-tech/invoicing/commit/9c782418a43d4d70cc8cea23019d6c5a66a96e86))
* add TDS and TCS source taxes to invoices ([897d69b](https://github.com/clarity-tech/invoicing/commit/897d69b3b45200af8cea122e62b0b1f1d76e2141))
* add TeamInvitationFactory for testing team invitations ([fb74351](https://github.com/clarity-tech/invoicing/commit/fb74351b7fdee0b384b076b3ba9241086ba3058d))
* add the Cloud Tasks queue connection ([9f901d0](https://github.com/clarity-tech/invoicing/commit/9f901d00fec2acd77a42ae1780f40f3df7046f2c))
* add UAE business user and organization seeding ([677248c](https://github.com/clarity-tech/invoicing/commit/677248c9141c6492a2626589542ce83705a64716))
* add UAE customers to seeding system ([677248c](https://github.com/clarity-tech/invoicing/commit/677248c9141c6492a2626589542ce83705a64716))
* add UAE tax templates for AED currency ([677248c](https://github.com/clarity-tech/invoicing/commit/677248c9141c6492a2626589542ce83705a64716))
* add UTM attribution to powered-by footer logo links ([8e5d1c4](https://github.com/clarity-tech/invoicing/commit/8e5d1c41b748efee09184fb9158dcc0c1fc31ad0))
* add Vitest frontend testing with 76 tests ([cbe4329](https://github.com/clarity-tech/invoicing/commit/cbe4329285cbcb4d78374d252b2ef59c4c216fb7))
* added livewire and money package ([5b9a95d](https://github.com/clarity-tech/invoicing/commit/5b9a95d0605dc3c0fd1cf74391e996c5605bff3f))
* added luvi-ui/laravel-luvi package that actis as shadcn for livewire ([c68d0ae](https://github.com/clarity-tech/invoicing/commit/c68d0aea0cb5d384a8630573e0e46added00a83c))
* added package composer require luvi-ui/laravel-luvi ([c68d0ae](https://github.com/clarity-tech/invoicing/commit/c68d0aea0cb5d384a8630573e0e46added00a83c))
* allow ProductionSeeder to run in any environment with confirmation ([70c77a1](https://github.com/clarity-tech/invoicing/commit/70c77a1cc456406af2a2a54d761ad026ebe48f88))
* commit recipient emails on tab/blur and validate them ([12e8506](https://github.com/clarity-tech/invoicing/commit/12e8506951357748693f6457903cf01b585464bd))
* Convert tax_rate storage from decimal to integer for precision ([06d326e](https://github.com/clarity-tech/invoicing/commit/06d326ea30082fdb17e28fc3884c3ed523417d07))
* country-aware tax rate dropdown on invoice line items ([ec1271b](https://github.com/clarity-tech/invoicing/commit/ec1271bbf52e39f487f1c9b14421d70a22e4df9b))
* country-code phone inputs with E.164 normalization ([cfecc36](https://github.com/clarity-tech/invoicing/commit/cfecc3673f1a1ffff215c72df92fa2413f822deb))
* Create comprehensive Laravel factories for all models ([9e7e4d7](https://github.com/clarity-tech/invoicing/commit/9e7e4d7ef7e71e1b16e8044aacb447785a4a2343))
* **customers:** soft delete with trash & restore ([87f77e8](https://github.com/clarity-tech/invoicing/commit/87f77e8a83bcdef4274ecaff7c6c4dc612de1e3b))
* **dashboard:** convert multi-currency totals to org currency via FX rates ([63b2a18](https://github.com/clarity-tech/invoicing/commit/63b2a18e54506bf66bf73dafaaa780e07a157265))
* default new invoice numbering to the short INV-26001 form ([911e719](https://github.com/clarity-tech/invoicing/commit/911e719e05153e65f9feca4167b8472e25327c2b))
* **email:** prefill Cc with organization emails in send dialog ([d3f92ac](https://github.com/clarity-tech/invoicing/commit/d3f92ac2ae15cf4c3200bfb80a1ba056bcb263c4))
* **emails:** add logo footer lockup and consistent brand slogans ([259065d](https://github.com/clarity-tech/invoicing/commit/259065d65b5226f70bb9af39772a6b26926e36fe))
* **emails:** embed brand logo inline (CID) so it always renders ([7b884bf](https://github.com/clarity-tech/invoicing/commit/7b884bfc9070b59a68b9932a3af12bc37cd7563c))
* **emails:** make the subject attribution tag configurable across all emails ([5b4cccc](https://github.com/clarity-tech/invoicing/commit/5b4cccc0815eaf60c612016910511d394b1625d3))
* **emails:** rework document email footer branding ([50c4b8a](https://github.com/clarity-tech/invoicing/commit/50c4b8a6d9ca16465abf875bc35310f68985a25b))
* **email:** styled customizable emails with attachments and View Online toggle ([980a0a7](https://github.com/clarity-tech/invoicing/commit/980a0a729b0c394a135ef29982d5095ccfc59c5e))
* Enable email verification and improve test coverage ([2099722](https://github.com/clarity-tech/invoicing/commit/209972228eb0b2ae25d0e8e96cc028ad536d4bf4))
* enhance Livewire components with organization setup integration ([08df54c](https://github.com/clarity-tech/invoicing/commit/08df54c4d57fe2a11590033c53ffdf196ee42fc7))
* enhance numbering series UI with automatic creation awareness ([2700c32](https://github.com/clarity-tech/invoicing/commit/2700c3286993e7ef373dc5a01599738411221a95))
* enhance organization UI with country-based smart defaults ([4eb93b4](https://github.com/clarity-tech/invoicing/commit/4eb93b4c770d136342a4fa5ed6a53fd1f7afe79a))
* front Cloud Run with a Cloudflare Worker ([3efd6a9](https://github.com/clarity-tech/invoicing/commit/3efd6a9444de17d5aead8d87ab2194c75225408c))
* implement comprehensive H5BP nginx configuration sync system ([ade6188](https://github.com/clarity-tech/invoicing/commit/ade61880d3bc87d27d767575b13062f72fe6c93b))
* implement comprehensive integer-only financial architecture ([dfd41a0](https://github.com/clarity-tech/invoicing/commit/dfd41a03a25153b44c6e2e8e8403dfb9b5662d50))
* implement comprehensive Laravel translation system and production seeders ([2384533](https://github.com/clarity-tech/invoicing/commit/2384533b0a38085346ef01b702848fbc18d5b39e))
* implement comprehensive organization setup wizard and tracking ([487b4e4](https://github.com/clarity-tech/invoicing/commit/487b4e41dd74008d3c36722369594679d565b94f))
* implement financial year business logic ([caaf6fe](https://github.com/clarity-tech/invoicing/commit/caaf6fee556ddce8bdad548ece0a340a25da4a16))
* implement invoice numbering series core infrastructure ([86c3117](https://github.com/clarity-tech/invoicing/commit/86c3117d5a859cba4dbf0c378b906026c8ba6483))
* implement multi-currency support with akaunting/laravel-money package ([076274b](https://github.com/clarity-tech/invoicing/commit/076274b92120e17a3fe5f0f0d4992a0a9f5f0042))
* integrate numbering series with invoice creation workflow ([86c3117](https://github.com/clarity-tech/invoicing/commit/86c3117d5a859cba4dbf0c378b906026c8ba6483))
* **invoices:** promote draft to sent after email is sent ([9623f37](https://github.com/clarity-tech/invoicing/commit/9623f37cd44edaf719b0739e961e4a1b910072c6))
* **invoices:** soft delete with trash & restore ([87f77e8](https://github.com/clarity-tech/invoicing/commit/87f77e8a83bcdef4274ecaff7c6c4dc612de1e3b))
* let customers choose which contacts auto-fill email recipients ([0ae9b18](https://github.com/clarity-tech/invoicing/commit/0ae9b1811c81f6da2dd418f6a3112244d19fb725))
* **locations:** country-aware tax-id label on location forms ([e4f3beb](https://github.com/clarity-tech/invoicing/commit/e4f3bebcc9446417d1fe4a5126e05fa1a941fb64))
* **locations:** country-aware tax-id label on location forms, setup wizard and PDFs ([e4f3beb](https://github.com/clarity-tech/invoicing/commit/e4f3bebcc9446417d1fe4a5126e05fa1a941fb64))
* **locations:** country-aware tax-id labels and neutral tax_id column ([c16ee77](https://github.com/clarity-tech/invoicing/commit/c16ee77a09411a6fe706686cf379e56120155b66))
* **locations:** country-aware tax-id on PDF and setup wizard ([e4f3beb](https://github.com/clarity-tech/invoicing/commit/e4f3bebcc9446417d1fe4a5126e05fa1a941fb64))
* **locations:** soft delete customer locations with inline restore ([87f77e8](https://github.com/clarity-tech/invoicing/commit/87f77e8a83bcdef4274ecaff7c6c4dc612de1e3b))
* **mail:** add brand mark logo to email header ([3bd5412](https://github.com/clarity-tech/invoicing/commit/3bd541264623f4dfc442c223cee1af3b66b55de4))
* **mail:** set organization contact as Reply-To on document emails ([2671b25](https://github.com/clarity-tech/invoicing/commit/2671b2531474937475afa686264700a5ef0b0b11))
* make document email send defaults configurable, default view-online on ([b9971df](https://github.com/clarity-tech/invoicing/commit/b9971dfd9087bdffacc5fe68bfc6b3265eadf737))
* make email header logo a clickable UTM-tagged link ([5b33f98](https://github.com/clarity-tech/invoicing/commit/5b33f98143db8d49a51f532a32c92358f9cf306a))
* make email logo strategy configurable (external URL vs embedded) ([159eefd](https://github.com/clarity-tech/invoicing/commit/159eefd70bcc134a5b13404129765ed0bb542603))
* migrate auth pages from Blade to Inertia Vue ([6387b49](https://github.com/clarity-tech/invoicing/commit/6387b4992a60c79e024213de631cedd7f53ddaa1))
* migrate browser tests from Dusk to Pest Browser ([4e804e4](https://github.com/clarity-tech/invoicing/commit/4e804e4080c48002fb7924bfb7247f554b5b77fe))
* migrate customer manager from Livewire to Inertia controller + Vue ([bdf20e8](https://github.com/clarity-tech/invoicing/commit/bdf20e85960466b6c90921398ff593e6433bd349))
* migrate dashboard from Livewire to Inertia controller + Vue page ([6387b49](https://github.com/clarity-tech/invoicing/commit/6387b4992a60c79e024213de631cedd7f53ddaa1))
* migrate invoice list, form, and numbering series to Inertia ([bdf20e8](https://github.com/clarity-tech/invoicing/commit/bdf20e85960466b6c90921398ff593e6433bd349))
* migrate organization manager + setup wizard to Inertia ([bdf20e8](https://github.com/clarity-tech/invoicing/commit/bdf20e85960466b6c90921398ff593e6433bd349))
* migrate profile and team management to Inertia Vue pages ([bdf20e8](https://github.com/clarity-tech/invoicing/commit/bdf20e85960466b6c90921398ff593e6433bd349))
* multi-location for customer ([d392691](https://github.com/clarity-tech/invoicing/commit/d392691c878f3c1760cec9fda7110d164decd9d5))
* **onboarding:** invite-first registration for member-only users ([78a343a](https://github.com/clarity-tech/invoicing/commit/78a343a0dbf56e0beed3b7cff8443e33bd553819))
* organization logo upload ([4500387](https://github.com/clarity-tech/invoicing/commit/4500387383b5c6ce43bc188fb04862e004183987))
* organization logo upload ([4500387](https://github.com/clarity-tech/invoicing/commit/4500387383b5c6ce43bc188fb04862e004183987))
* **organizations:** enable owners to create and manage multiple orgs ([7ffa74c](https://github.com/clarity-tech/invoicing/commit/7ffa74cf6a4b5c5a311be65bc49dd73d204b9c1a))
* **pdf:** add chrome service for pdf generation ([4bc7a6c](https://github.com/clarity-tech/invoicing/commit/4bc7a6cb9b7c313027ac0bf0f8e152ee85e6b73f))
* pin document footer to bottom of last PDF page ([db46dd8](https://github.com/clarity-tech/invoicing/commit/db46dd85c2920222aa1fa019d3a4c591c092f9b4))
* polish invoice email branding and rework UTM attribution ([3d633bd](https://github.com/clarity-tech/invoicing/commit/3d633bdbe3fec9fc7899d695cb11848b5d435bca))
* **rbac:** enforce role-based permissions and surface member management ([01a6ae3](https://github.com/clarity-tech/invoicing/commit/01a6ae3121db903c1eb209d4159eac9cd8f8d62b))
* rebrand to InvoiceInk with violet theme and Organization terminology ([b9bd8d7](https://github.com/clarity-tech/invoicing/commit/b9bd8d70e30c5ff9e3393a44b7664b09ec0e2eb8))
* redesign invoice form to single-page layout with email sending ([d67ff3a](https://github.com/clarity-tech/invoicing/commit/d67ff3ab61a3aa399b746f880797109dcf3583de))
* redesign invoice/estimate templates to Zoho Books style with bank details ([bf7f092](https://github.com/clarity-tech/invoicing/commit/bf7f0923c5b0784f028b4a44350097fcde9bd651))
* redesign invoice/estimate templates with Swiss Precision aesthetic ([35f3f48](https://github.com/clarity-tech/invoicing/commit/35f3f48db309e0ff00940ed3b08327f2ef9289c9))
* redesign landing page with dark theme and invoice mockup ([b44496f](https://github.com/clarity-tech/invoicing/commit/b44496f479d2456535d07442f19e803662bbfb94))
* remember invoice email recipients and pre-select them on send ([12e8506](https://github.com/clarity-tech/invoicing/commit/12e8506951357748693f6457903cf01b585464bd))
* remove alternate financial year for india ([82f01d6](https://github.com/clarity-tech/invoicing/commit/82f01d695dc36e52fa074a281691285ba3021752))
* replace all hardcoded text with translation strings across all views ([07b5d0f](https://github.com/clarity-tech/invoicing/commit/07b5d0f1df5af23fa5ca6795e5f2c3c4247b75a3))
* replace alpine-chrome with Gotenberg for PDF generation ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* replace UUID with ULID for invoices ([fbdfe0f](https://github.com/clarity-tech/invoicing/commit/fbdfe0fa17b61e44f7df21f11a8771f95290a2b6))
* run artisan against production from the Actions tab ([c156d5e](https://github.com/clarity-tech/invoicing/commit/c156d5ebe3382cb59171b034e657b18b43e9f814))
* seed the features shipped since the seeders were last touched ([faf08af](https://github.com/clarity-tech/invoicing/commit/faf08af044379c0f90ecefba3b853a4ce4e75dd9))
* Setup Laravel Dusk with Selenium container and automatic screenshots ([fad1a6d](https://github.com/clarity-tech/invoicing/commit/fad1a6d0647c1963ea749b16c1f6717054bd1d15))
* show InvoiceInk logo in document footer branding ([9980805](https://github.com/clarity-tech/invoicing/commit/9980805b8e83bb2c24516ad0249d7adf1c1422e7))
* soft delete for invoices, customers, and locations with trash & restore ([87f77e8](https://github.com/clarity-tech/invoicing/commit/87f77e8a83bcdef4274ecaff7c6c4dc612de1e3b))
* split GST into CGST/SGST/UTGST/IGST by place of supply ([981843e](https://github.com/clarity-tech/invoicing/commit/981843e8da8db385052c0eef31420d82c62a2342))
* store invoice/estimate attachments on the document ([6523585](https://github.com/clarity-tech/invoicing/commit/6523585623512fd5aa845b800cdaca579f0e5ad5))
* streamline organization setup wizard to 3 steps ([d55ad96](https://github.com/clarity-tech/invoicing/commit/d55ad96790d6a0a049c7265e28101aac64965868))
* suggest known contacts in email recipient fields ([7e29348](https://github.com/clarity-tech/invoicing/commit/7e29348312f3c38fa271af7c15e856936fc6e816))
* switch from npm to bun for faster frontend tooling ([3511413](https://github.com/clarity-tech/invoicing/commit/3511413ae901d5030b415763ba539d3d75dc1afa))
* Transform single-tenant app to multitenant SaaS platform ([42c7a8d](https://github.com/clarity-tech/invoicing/commit/42c7a8d6cb3fa929d1b7d5e0fa5bbe704bdec498))
* unify InvoiceInk logo into a single lockup across app and emails ([dfd53df](https://github.com/clarity-tech/invoicing/commit/dfd53df73fd20fa5d4daad157e7d305302dab603))
* update database schema for financial year support ([6f72566](https://github.com/clarity-tech/invoicing/commit/6f72566c2fcbdf78a7145ed4ec0053d20dcbffb9))
* upgrade Inertia.js from v2 to v3 ([8d4168e](https://github.com/clarity-tech/invoicing/commit/8d4168e34c3882d72da2082012fb42a9a0a4cee8))
* upgrade Laravel 12 to 13 with dependency cleanup ([b7db91d](https://github.com/clarity-tech/invoicing/commit/b7db91d7c833c59f6134ee199001923bbc569364))
* upgrade Livewire from v3 to v4 ([bdb3760](https://github.com/clarity-tech/invoicing/commit/bdb3760c8d4c78afbe57698acc6ae8b14029e042))
* upgrade to PHP 8.5 with infrastructure updates ([5c33edc](https://github.com/clarity-tech/invoicing/commit/5c33edcb18b8d4f996f0d3f3ca505b0ac7a5ed4c))
* upgrade to Tailwind CSS v4 with modern build configuration ([bcfb577](https://github.com/clarity-tech/invoicing/commit/bcfb5778d8249e4f01ab7062594977598fa26637))
* upgrade Vite 7→8 and laravel-vite-plugin 2→3 ([cbe4329](https://github.com/clarity-tech/invoicing/commit/cbe4329285cbcb4d78374d252b2ef59c4c216fb7))
* UX improvements phases 1-3 for small business users ([d6a08dd](https://github.com/clarity-tech/invoicing/commit/d6a08dd5dd5552ea0cbd80570c905030f09aa9ec))
* UX improvements phases 4-8 — empty states, a11y, mobile, safety, polish ([d6a08dd](https://github.com/clarity-tech/invoicing/commit/d6a08dd5dd5552ea0cbd80570c905030f09aa9ec))
* wire EmailModal to template service with TipTap editor ([c5748d3](https://github.com/clarity-tech/invoicing/commit/c5748d38fd962c125b895fe7894c99693ae0d672))


### Bug Fixes

* 404 error ([8043fbc](https://github.com/clarity-tech/invoicing/commit/8043fbccffab30f8e88b98de168698c04b89eb26))
* add authorization, rate limiting, org-scoped validation, and brand color fixes ([51e6e4d](https://github.com/clarity-tech/invoicing/commit/51e6e4dabfcc59a83238258632e2c246e4aa7333))
* add default currency to createCompanyWithLocation test helper ([d2f4ced](https://github.com/clarity-tech/invoicing/commit/d2f4cedbf0000d9eb16dcbb1b18d207a43b38ead))
* add OrganizationScope to InvoiceNumberingSeries model (M7) ([76232b4](https://github.com/clarity-tech/invoicing/commit/76232b410e6eb17ee6f40b24cf29572e527bd7a6))
* add public homepage route for unauthenticated visitors ([b9bd8d7](https://github.com/clarity-tech/invoicing/commit/b9bd8d70e30c5ff9e3393a44b7664b09ec0e2eb8))
* address critical security issues C3-C5 from code review ([1114295](https://github.com/clarity-tech/invoicing/commit/1114295912a1aa31e58d3972c7fc581f618bc84f))
* align email editor preview with sent email and balance CTA gaps ([b2683e7](https://github.com/clarity-tech/invoicing/commit/b2683e7c76ab5de3c33e64e88478cc7cf44600b5))
* align factories with the current schema and enums ([9b819de](https://github.com/clarity-tech/invoicing/commit/9b819dedbe7ee1b2636258d908b42103ac846963))
* align organization setup wizard with app design system ([9f83c2b](https://github.com/clarity-tech/invoicing/commit/9f83c2bfa147596452b91b13188c75393efd0fb6))
* align the production image with Cloud Run's container contract ([fc1ccc4](https://github.com/clarity-tech/invoicing/commit/fc1ccc4835062ba32541a982e6972dad9325a0b1))
* build pipeline ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* build pipeline ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* **ci:** add bun run build to test-octane job for Vite manifest ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** add Chrome PDF service to PHP test job ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** add concurrency to Sail image build to cancel stale runs ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** create .env file for browser and PHP tests ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** fix test-images job — correct image tag, port, and env vars ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** install bun deps before Playwright in test-octane job ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** prevent duplicate CI runs on push + PR ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** remove duplicate tests.yaml workflow ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* **ci:** replace npm with bun in tests workflow ([3511413](https://github.com/clarity-tech/invoicing/commit/3511413ae901d5030b415763ba539d3d75dc1afa))
* **ci:** run stable browser tests only in production image job ([b544955](https://github.com/clarity-tech/invoicing/commit/b5449553508166ea5c13657354720546d86a0482))
* **ci:** update Sail image build path from 8.4 to 8.5 ([5c33edc](https://github.com/clarity-tech/invoicing/commit/5c33edcb18b8d4f996f0d3f3ca505b0ac7a5ed4c))
* **ci:** use PHP 8.5 consistently across all CI jobs ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* clarify customer search filters current page only, cache primaryEmail ([216b8f4](https://github.com/clarity-tech/invoicing/commit/216b8f410ef727b7947f958304de0b4ea8f7f98d))
* correct database seeding commands and update documentation ([70c77a1](https://github.com/clarity-tech/invoicing/commit/70c77a1cc456406af2a2a54d761ad026ebe48f88))
* correct production seeder tax rates and make seeding idempotent ([8262fa0](https://github.com/clarity-tech/invoicing/commit/8262fa00fb5aafda9f8bbdb9c8da03951e5151b9))
* create org before location to avoid invalid locatable_id (M4) ([2055cac](https://github.com/clarity-tech/invoicing/commit/2055cac937999c2f3c849e66e98852f7b408b528))
* **customers:** block deletion of location used by invoices, keep primary intact ([4677529](https://github.com/clarity-tech/invoicing/commit/46775294890c2afe618d466614cad291c7d9bf20))
* **customers:** keep contact email when name is empty ([183af3c](https://github.com/clarity-tech/invoicing/commit/183af3cf9aa956c4365831b7d1615bb337aa8201))
* **database:** switch local and production from PostgreSQL to MySQL ([6f50b71](https://github.com/clarity-tech/invoicing/commit/6f50b71b807f82147ce676f7fac6f57b32eeabf0))
* **db:** add default currency to teams in UserSeeder ([f736bb9](https://github.com/clarity-tech/invoicing/commit/f736bb90ce1a503790fa4f99202742d2ba1a4a61))
* **db:** correct tax calculation in InvoiceSeeder ([2550f9c](https://github.com/clarity-tech/invoicing/commit/2550f9ce29dad33b8c71b2597a9160240e9a3a2d))
* **docker:** pre-generate Wayfinder TS in php-deps stage so Bun build doesn't need PHP ([801c67e](https://github.com/clarity-tech/invoicing/commit/801c67e8cc86f967ef9e2d1240267025f68bdc3b))
* **documents:** revert PDF page margins to compact values ([7f3a0da](https://github.com/clarity-tech/invoicing/commit/7f3a0da868dc98c4480a2aea3433f128222f0b80))
* Enable PdfService tests with graceful error handling ([9e6ff02](https://github.com/clarity-tech/invoicing/commit/9e6ff02cdf2236ed7a72cc0d9d6a271d514e5d5a))
* estimate conversion to invoice conflict with existing invoice number ([f9be3b9](https://github.com/clarity-tech/invoicing/commit/f9be3b962ca243f3476e0938c0c0f4cc31e5ba74))
* finalize @tailwindcss/forms plugin version compatibility ([bcfb577](https://github.com/clarity-tech/invoicing/commit/bcfb5778d8249e4f01ab7062594977598fa26637))
* fix test failures and tax rate formatting ([f679f59](https://github.com/clarity-tech/invoicing/commit/f679f59199ebfbadc75119dede10f8d3a208df9e))
* friendly error message ([8409e8d](https://github.com/clarity-tech/invoicing/commit/8409e8d3aed1b5bcfebcbb9fa3837081895f2240))
* friendly error messages for nested array fields and align line-item inputs on the same row ([0cb43ec](https://github.com/clarity-tech/invoicing/commit/0cb43ecbc9cb58c571a75855ceb57552a311aba0))
* gap between logo icon and brand name ([c98eca0](https://github.com/clarity-tech/invoicing/commit/c98eca0dfdad601f140fce129c4aee1511cb1e23))
* improve browser test authentication helpers and update PLAN.md ([c9a0b7b](https://github.com/clarity-tech/invoicing/commit/c9a0b7b4016b77c9bd484418d65abdbb9ce0b4e6))
* improve CustomerManager query reliability and organization scoping ([945dddf](https://github.com/clarity-tech/invoicing/commit/945dddf85ef9f2993e71d6d885cfb76767f9b89d))
* invoice email amount_due includes tax and pdf attachment works on queued mailable ([9f7dceb](https://github.com/clarity-tech/invoicing/commit/9f7dcebefb54a85fd01805ab2262e639b1570a10))
* invoice seeder currency handling for AED support ([677248c](https://github.com/clarity-tech/invoicing/commit/677248c9141c6492a2626589542ce83705a64716))
* **invoice-form:** prevent currency-symbol overlap on unit price input ([827485e](https://github.com/clarity-tech/invoicing/commit/827485eb43989720aaaa98ca94f43d404b652678))
* **invoices:** align line-item inputs on the same row ([0cb43ec](https://github.com/clarity-tech/invoicing/commit/0cb43ecbc9cb58c571a75855ceb57552a311aba0))
* **invoices:** recompute exchange rate when duplicating an invoice ([9815544](https://github.com/clarity-tech/invoicing/commit/981554467acb4d6fea96dfe0397b92a49b8173dd))
* **invoices:** reflect server status promotion in edit form dropdown ([e70cab4](https://github.com/clarity-tech/invoicing/commit/e70cab43a95c4d8ccb97aee5550ff7d9307dd8e2))
* issue showing wrong tax percentage in invoice view and PDF download ([5c96271](https://github.com/clarity-tech/invoicing/commit/5c96271d63e8dc6beda194c79a98d443469f5b4d))
* logo size issue ([4500387](https://github.com/clarity-tech/invoicing/commit/4500387383b5c6ce43bc188fb04862e004183987))
* **logo:** allow and sanitize SVG organization logo uploads ([a277732](https://github.com/clarity-tech/invoicing/commit/a27773273dfb025b392c87233c388e44955457b0))
* logout action as it was giving normal html response so inertia was showing in modal ([78e30fa](https://github.com/clarity-tech/invoicing/commit/78e30fadb44bbbdeb3ad22f23c5f3a51a59d037d))
* make customer_shipping_location_id nullable to match DB schema (M8) ([372201c](https://github.com/clarity-tech/invoicing/commit/372201c0b34d61ea4acb2197e1767dd298396778))
* make invoice email logo legible in Gmail dark mode ([51c24f3](https://github.com/clarity-tech/invoicing/commit/51c24f3bc2ae3fe219dcb1d1913fc8f5680de651))
* make org switcher reactive to current team prop changes ([0e754dd](https://github.com/clarity-tech/invoicing/commit/0e754dd96fd24360a64332c0ffa1c3a7ea1c4c20))
* minor text fix on team invitation template ([b474bbd](https://github.com/clarity-tech/invoicing/commit/b474bbdc52bbc9c8cc75f91bd29e781d6a9be2d7))
* **modals:** defer body Teleports until mount to avoid SSR hydration crash ([9c7fd55](https://github.com/clarity-tech/invoicing/commit/9c7fd5567da466a4ae879cab14f66ed892b1cc56))
* navigation bar crash — null-guard auth and flash page props ([badd087](https://github.com/clarity-tech/invoicing/commit/badd08700ae3155a061c3304a0e6ce1956a5e9bb))
* navigation UX — close menus on navigate, click-away, add Settings link ([badd087](https://github.com/clarity-tech/invoicing/commit/badd08700ae3155a061c3304a0e6ce1956a5e9bb))
* numbering series monthly reset tests failing on 29th-31st of month ([74b0e72](https://github.com/clarity-tech/invoicing/commit/74b0e725c1fe6abce4703581235b69de7524e7bd))
* **numbering:** remove any-verb redirect that hijacked series creation ([56c94f0](https://github.com/clarity-tech/invoicing/commit/56c94f0316a653311d3a4a2b6b9308cb1dffbd18))
* organization setup Complete Setup button not submitting ([d2c03a6](https://github.com/clarity-tech/invoicing/commit/d2c03a63ae45c384eb1b7a87f18e18a722878fda))
* **organization:** accept website without http/s scheme ([28b9ba0](https://github.com/clarity-tech/invoicing/commit/28b9ba033d4ffda61ab3e86b4daa2c812dc77b1f))
* **organizations:** separate org name from company name across switcher, setup, and settings ([db39ec5](https://github.com/clarity-tech/invoicing/commit/db39ec5282df2e5cb7edd8d297d8c62e1e9942fd))
* **pdf:** set line-height 1.3 on company meta, address, and bank details ([13ae5b8](https://github.com/clarity-tech/invoicing/commit/13ae5b8b319e82949342675a70ffdf11fb716582))
* prevent invoice list actions column from clipping on desktop ([f655013](https://github.com/clarity-tech/invoicing/commit/f655013292161624a2f58ed30c19cd22c261e55b))
* prevent invoice numbering race condition with atomic lock (C1) ([1df820a](https://github.com/clarity-tech/invoicing/commit/1df820afd6ad2c69d9cd4aa0abdc162bb90a083a))
* prevent register email field from clearing on password visibility toggle ([9a51b2a](https://github.com/clarity-tech/invoicing/commit/9a51b2a313e14dda6fc39e136c8538bd556f5458))
* raise invoice line item description limit to 2000 characters ([f60f0ba](https://github.com/clarity-tech/invoicing/commit/f60f0baf94de00dbabab1d265a4c417fac093721))
* Refactor email handling to contact collections ([7e286b8](https://github.com/clarity-tech/invoicing/commit/7e286b834acf6c21095fbaf84450676c4bd136ef))
* register and update team issue ([f9d2fed](https://github.com/clarity-tech/invoicing/commit/f9d2fed5f4704d8def2e2358e2953749134312d1))
* remove stale references to deleted configs and npm ([400d0fc](https://github.com/clarity-tech/invoicing/commit/400d0fc2ffa2b3f1fca1aa511efe2d2531f99efb))
* render CI deploys through render.sh instead of duplicating it ([c2d5524](https://github.com/clarity-tech/invoicing/commit/c2d5524ecc769e0b1f0dd5c41713389429957b61))
* replace tech stack mention with Clarity Technologies link in footer ([b44496f](https://github.com/clarity-tech/invoicing/commit/b44496f479d2456535d07442f19e803662bbfb94))
* report why log reading failed instead of claiming there was no output ([c156d5e](https://github.com/clarity-tech/invoicing/commit/c156d5ebe3382cb59171b034e657b18b43e9f814))
* resolve 13 more test failures (Phase 3 test data and view fixes) ([f894315](https://github.com/clarity-tech/invoicing/commit/f894315110158df0712658820515770fa16dd0f5))
* resolve 16 test failures (Phase 1+2 quick wins and code bugs) ([f894315](https://github.com/clarity-tech/invoicing/commit/f894315110158df0712658820515770fa16dd0f5))
* resolve 7 medium priority bugs from codebase audit ([e1ce02a](https://github.com/clarity-tech/invoicing/commit/e1ce02a49dcc0d1b3728d8939050555f212769c1))
* resolve all 7 failing browser tests ([b544955](https://github.com/clarity-tech/invoicing/commit/b5449553508166ea5c13657354720546d86a0482))
* resolve all 70 test failures and 2 warnings (Phase 4 rewrites) ([f894315](https://github.com/clarity-tech/invoicing/commit/f894315110158df0712658820515770fa16dd0f5))
* resolve all remaining Livewire test validation issues ([d1d0329](https://github.com/clarity-tech/invoicing/commit/d1d0329d40e1ae7744e92ebf51e83a0ef43bbe25))
* resolve blade template syntax issues in Livewire components ([1e4a5c3](https://github.com/clarity-tech/invoicing/commit/1e4a5c3e0300f298d33dd6bfb6e0e063214beda4))
* resolve critical and high priority bugs from codebase audit ([5d8703a](https://github.com/clarity-tech/invoicing/commit/5d8703a3d26f425e775eb4f3f8f22c9c6d0fe538))
* resolve Dusk browser test freezing and hanging issues ([e70621a](https://github.com/clarity-tech/invoicing/commit/e70621afcca29a17d00c1d82984bbdbc8e5252c5))
* Resolve flaky pagination test in InvoiceWizardTest ([6ff737c](https://github.com/clarity-tech/invoicing/commit/6ff737ca86bc201edd98a68aed2b044fb9edd2b0))
* resolve foreign key relationships for renamed Team to Organization model ([a940014](https://github.com/clarity-tech/invoicing/commit/a9400149c1ec4b5e0b7c857ba0c333196818a122))
* resolve invoice number format test regressions ([90402e9](https://github.com/clarity-tech/invoicing/commit/90402e91764282d2509faacecc562bca4c50a89a))
* resolve Livewire validation issues and foreign key constraints ([d1d0329](https://github.com/clarity-tech/invoicing/commit/d1d0329d40e1ae7744e92ebf51e83a0ef43bbe25))
* resolve medium bugs [#6](https://github.com/clarity-tech/invoicing/issues/6)-12 from PRD ([3dc5ab7](https://github.com/clarity-tech/invoicing/commit/3dc5ab7584a0660ef13c74e44ef1c4c13ccde588))
* resolve Organization model database constraints and relationships ([1271c06](https://github.com/clarity-tech/invoicing/commit/1271c060673dcabed9d070ee362cd52a110906e7))
* resolve Playwright browser-setup EACCES permission error ([4e804e4](https://github.com/clarity-tech/invoicing/commit/4e804e4080c48002fb7924bfb7247f554b5b77fe))
* resolve session flash message tests and decimal tax rate validation ([d1d0329](https://github.com/clarity-tech/invoicing/commit/d1d0329d40e1ae7744e92ebf51e83a0ef43bbe25))
* resolve test failures and improve coverage infrastructure ([babe813](https://github.com/clarity-tech/invoicing/commit/babe81329eb40a9694044388a38d9eda3100e2e0))
* reverse job logs portably in artisan.sh ([7204240](https://github.com/clarity-tech/invoicing/commit/72042408cd917d69c809232011a2597eaf1a5194))
* roll dark-mode-safe logo out to team invite and auth emails ([ef22b3f](https://github.com/clarity-tech/invoicing/commit/ef22b3f6bc3b63842937f956cc33a36424d4dedd))
* serve email logo via hosted URL instead of CID attachment ([a999c7e](https://github.com/clarity-tech/invoicing/commit/a999c7ef441ff1e476e185adee3a355f764b0073))
* show all tax rates in org edit general info ([87a4e55](https://github.com/clarity-tech/invoicing/commit/87a4e5561225942fde7363b4702c16fb6d00ff28))
* stop the Sail image workflow clobbering the production :latest tag ([e487ac1](https://github.com/clarity-tech/invoicing/commit/e487ac16203c5632747ea818cc1d026c73083459))
* **storage:** serve logos and profile photos from a public-read S3 disk ([73db106](https://github.com/clarity-tech/invoicing/commit/73db10672ffda430846f756ef4886ddca21385e4))
* supervisor error ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* **temp:** redirect /teams/create to /organizations for now ([9fb3541](https://github.com/clarity-tech/invoicing/commit/9fb3541a8da95f072a1edaab6c6fe76366a6bfc0))
* test cases ([5f68417](https://github.com/clarity-tech/invoicing/commit/5f68417a5e170b5def4e32583c1d714e4b10e903))
* test cases ([5f68417](https://github.com/clarity-tech/invoicing/commit/5f68417a5e170b5def4e32583c1d714e4b10e903))
* update appNmae to InvoiceInk ([85bdb4a](https://github.com/clarity-tech/invoicing/commit/85bdb4a56ec059a0043ce3b444ad659eededb976))
* update browser tests for numbering series and invoice page changes ([74b0e72](https://github.com/clarity-tech/invoicing/commit/74b0e725c1fe6abce4703581235b69de7524e7bd))
* update production Dockerfiles for bun, Tailwind v4, and layer optimization ([a50a14d](https://github.com/clarity-tech/invoicing/commit/a50a14d6ffa396f1307c535259356b2415b36fd7))
* validate ULID format on public document routes ([4ca0a7b](https://github.com/clarity-tech/invoicing/commit/4ca0a7b3dc8124493dc80fb8825633d9730b3fc5))
* **validation:** friendly error messages for nested array fields ([0cb43ec](https://github.com/clarity-tech/invoicing/commit/0cb43ecbc9cb58c571a75855ceb57552a311aba0))
* widen the timeout ladder so PDF generation can complete ([9473077](https://github.com/clarity-tech/invoicing/commit/947307795afb9f44d5b68a45e5eb40219d761240))
* zero-rate export supplies and drop the unused customer_contacts table ([403fbd4](https://github.com/clarity-tech/invoicing/commit/403fbd472f368f2c447319dd468a934141e5a691))


### Performance

* consolidate dashboard queries from 20+ down to ~8 (H5) ([3f01925](https://github.com/clarity-tech/invoicing/commit/3f019255effe08fa0c32cc22e1ce0562af9fe153))
* fix N+1 query in customer list by using withCount ([bc392fe](https://github.com/clarity-tech/invoicing/commit/bc392feb0227e6bbf18af3394007061224b940f9))


### Refactoring

* address code review — reuse composables, fix bugs, DRY ([863b447](https://github.com/clarity-tech/invoicing/commit/863b447ee04e1b2bd85519aa716293ad8d622298))
* adjust notes and t&c textarea height ([caa0ca4](https://github.com/clarity-tech/invoicing/commit/caa0ca4bba3b34e28305156d98ef8e5526d2f863))
* align with Laravel 13 starter patterns ([ca95910](https://github.com/clarity-tech/invoicing/commit/ca959109e3cc622e01576a35c023ede2d2c1b5b7))
* clean up obsolete nginx configuration files ([ade6188](https://github.com/clarity-tech/invoicing/commit/ade61880d3bc87d27d767575b13062f72fe6c93b))
* comprehensive database factory pattern implementation ([3fe0230](https://github.com/clarity-tech/invoicing/commit/3fe0230b9c0e9d8b705715d70275e634dcafe40d))
* disable Jetstream teams feature for organization-centric architecture ([d4c4616](https://github.com/clarity-tech/invoicing/commit/d4c461656d4891690695eb2f0da4fd8ea9977f26))
* extract large Livewire views into Blade partials ([ada8feb](https://github.com/clarity-tech/invoicing/commit/ada8feb215c73a4efc6e01a9b72ed37511578b15))
* **gst:** update gst rates of india as per gst 2.0 structure ([cbc4304](https://github.com/clarity-tech/invoicing/commit/cbc43043d38eea675c45ba1ffc4db15206212259))
* improve UX consistency across all pages ([967538a](https://github.com/clarity-tech/invoicing/commit/967538a19aa7a9605531a5da0b5b50e46e7ff247))
* make the subject label and subject sentence in one line ([1963c9c](https://github.com/clarity-tech/invoicing/commit/1963c9cad32ecc40e0c4fb3e9d141db9f821ab13))
* merge Team and Company models into single Organization concept ([a940014](https://github.com/clarity-tech/invoicing/commit/a9400149c1ec4b5e0b7c857ba0c333196818a122))
* **pdf:** remove unused spatie/browsershot package ([4bc7a6c](https://github.com/clarity-tech/invoicing/commit/4bc7a6cb9b7c313027ac0bf0f8e152ee85e6b73f))
* **pdf:** switch to http-based pdf generation ([4bc7a6c](https://github.com/clarity-tech/invoicing/commit/4bc7a6cb9b7c313027ac0bf0f8e152ee85e6b73f))
* redesign profile page and nav user dropdown ([5a80692](https://github.com/clarity-tech/invoicing/commit/5a806922cecc4642aec422679c2c47da1059859b))
* remove deprecated Company and Team components ([209def2](https://github.com/clarity-tech/invoicing/commit/209def2e08cf5199ca94468546ffbd5a707ec7de))
* remove laravel/jetstream, inline essential code into app ([0a0c056](https://github.com/clarity-tech/invoicing/commit/0a0c05672ab91819b7351a86388f20ddabb33f9d))
* remove Livewire and complete Inertia migration cleanup ([bdf20e8](https://github.com/clarity-tech/invoicing/commit/bdf20e85960466b6c90921398ff593e6433bd349))
* remove obsolete Jetstream team action classes ([b9ace54](https://github.com/clarity-tech/invoicing/commit/b9ace5474fec6dc28a40315be3c30aff374bd745))
* remove obsolete team components ([b9ace54](https://github.com/clarity-tech/invoicing/commit/b9ace5474fec6dc28a40315be3c30aff374bd745))
* remove old Blade email templates, unify on custom-document ([ede82fb](https://github.com/clarity-tech/invoicing/commit/ede82fb8e3816b25c13fe4f7a057d77379d7ad46))
* rename companyLocations to organizationLocations ([78de381](https://github.com/clarity-tech/invoicing/commit/78de381e26e073bbb0acf8126e72cd600e63ea45))
* rename companyLocations to organizationLocations in InvoiceWizard ([3c9eab2](https://github.com/clarity-tech/invoicing/commit/3c9eab2bd6d31e3a6e4a5c18895ba0be0515fdab))
* replace all remaining blue/gray-800 colors with brand palette ([ace8be5](https://github.com/clarity-tech/invoicing/commit/ace8be59b572ad504aaca837cb0cc34a94e8fd9b))
* replace associative arrays with InvoiceTotals value object ([7798d20](https://github.com/clarity-tech/invoicing/commit/7798d20b03c54445850dba3632a6744b955e109b))
* replace hardcoded messages with translation strings and use array validation syntax ([e33abbc](https://github.com/clarity-tech/invoicing/commit/e33abbcfbb7cab89296e63fca7f99339a8c182a2))
* replace manual auth checks with policy authorization (H3/M3) ([748d45b](https://github.com/clarity-tech/invoicing/commit/748d45bc33e1993f518e0916a63c824d6143d2be))
* reposition contact fields in setup step 1 ([a2fe41f](https://github.com/clarity-tech/invoicing/commit/a2fe41fb5e50bf386cccc76af3771add028a7e29))
* serve predefined tax rates from Country enum instead of per-org table ([7c12c7d](https://github.com/clarity-tech/invoicing/commit/7c12c7dded4603c2abd797eefa18d74536435954))
* simplify UX code after review — remove duplication, fix quality issues ([d6a08dd](https://github.com/clarity-tech/invoicing/commit/d6a08dd5dd5552ea0cbd80570c905030f09aa9ec))
* switch production image to ServerSideUp FrankenPHP ([1ffe17f](https://github.com/clarity-tech/invoicing/commit/1ffe17fa338f962ec4794ef454a7f45bddbecc22))
* **tenancy:** drop personal_team flag, force setup for every signup ([b5641b7](https://github.com/clarity-tech/invoicing/commit/b5641b773a4c1e396018d68eefb78e923dd20022))
* update factories for organization-centric architecture ([0ec9d23](https://github.com/clarity-tech/invoicing/commit/0ec9d232c927e02800750484cbf69d5816a21f9d))
* update invoice and customer management components ([f17c514](https://github.com/clarity-tech/invoicing/commit/f17c514c683c908630d59ba71d2e7de8af797180))
* update models to use Laravel 11+ casts() method convention ([4069290](https://github.com/clarity-tech/invoicing/commit/40692909ef5569877161ba2ca78b8d7086a97461))
* update navigation and routing for organizations ([07cf313](https://github.com/clarity-tech/invoicing/commit/07cf313c1446d8c600ecb22374747882158ca08c))
* update Organization model and management components ([b7893ed](https://github.com/clarity-tech/invoicing/commit/b7893ed0fdd5a10cc5edb1d6c1891ba5b850b7c0))
* update public views and controllers for new architecture ([2107e72](https://github.com/clarity-tech/invoicing/commit/2107e72506516087b4f29a19f7c28b3b93fbc5b3))
* update services for organization-centric architecture ([637e105](https://github.com/clarity-tech/invoicing/commit/637e10529e2473c54e46b42e810207792afd2cb2))
* update some placeholder texts and informational texts ([520c138](https://github.com/clarity-tech/invoicing/commit/520c13844ffeee1253b6848329c6c9a0ea008630))


### Documentation

* Docs:  ([1ffe17f](https://github.com/clarity-tech/invoicing/commit/1ffe17fa338f962ec4794ef454a7f45bddbecc22))
* add CLAUDE.md with project configuration and commands ([b3c89d2](https://github.com/clarity-tech/invoicing/commit/b3c89d23990c4d8005f92d8e3100ca70a0311e41))
* add comprehensive invoice numbering series documentation ([2700c32](https://github.com/clarity-tech/invoicing/commit/2700c3286993e7ef373dc5a01599738411221a95))
* add comprehensive translation system and production seeder documentation ([70c77a1](https://github.com/clarity-tech/invoicing/commit/70c77a1cc456406af2a2a54d761ad026ebe48f88))
* Add Laravel Pint formatting requirements to CLAUDE.md ([fad1a6d](https://github.com/clarity-tech/invoicing/commit/fad1a6d0647c1963ea749b16c1f6717054bd1d15))
* add presentation slides and screenshots ([4459ec6](https://github.com/clarity-tech/invoicing/commit/4459ec6e8ef106f2f8c4bd196e877fce3af969dd))
* correct §9.4 to describe the Worker that was actually built ([4077109](https://github.com/clarity-tech/invoicing/commit/4077109849f67efa855566f947fb57b522772816))
* correct stale seeder output and project documentation ([fce2d34](https://github.com/clarity-tech/invoicing/commit/fce2d349ae8b8c773c85e04b4e34035e7390c9b6))
* cover the artisan workflow, numbering default and logging role ([4077109](https://github.com/clarity-tech/invoicing/commit/4077109849f67efa855566f947fb57b522772816))
* create PRD.md with project requirements ([c94dd73](https://github.com/clarity-tech/invoicing/commit/c94dd730931c883cd765ebece9b85981cc1b4b37))
* Enhance CLAUDE.md with comprehensive architecture guidance ([907c396](https://github.com/clarity-tech/invoicing/commit/907c396c528347d9d023e83412abc991fb465723))
* **plan:** update plan for pdf generation service ([4bc7a6c](https://github.com/clarity-tech/invoicing/commit/4bc7a6cb9b7c313027ac0bf0f8e152ee85e6b73f))
* record full end-to-end verification on production ([eb4e209](https://github.com/clarity-tech/invoicing/commit/eb4e2094d1fc81426fe64f7150ac50e361cf0c25))
* record live verification results ([68c7c34](https://github.com/clarity-tech/invoicing/commit/68c7c343b9b49fbebb58b80cdbcd31eacdbdf3bb))
* record SMTP verification and the migrate:fresh seeding trap ([77d4c35](https://github.com/clarity-tech/invoicing/commit/77d4c354ca185ef14f92a3c063f8f81412bdec8a))
* record the Cloud Run architecture and migration progress ([95dc360](https://github.com/clarity-tech/invoicing/commit/95dc36047fd769da3cb37317abb638f3e3becee4))
* rewrite PRD with comprehensive codebase audit findings ([7e286b8](https://github.com/clarity-tech/invoicing/commit/7e286b834acf6c21095fbaf84450676c4bd136ef))
* update CLAUDE.md and skills for Livewire v4, no Jetstream ([3a8092c](https://github.com/clarity-tech/invoicing/commit/3a8092cb565f96c66b67ff4a74535414c79b5a33))
* update CLAUDE.md for current stack and add resources/js to eslint ignores ([400d0fc](https://github.com/clarity-tech/invoicing/commit/400d0fc2ffa2b3f1fca1aa511efe2d2531f99efb))
* update PLAN.md and PRD.md with completed tasks and features ([3dc5ab7](https://github.com/clarity-tech/invoicing/commit/3dc5ab7584a0660ef13c74e44ef1c4c13ccde588))
* update PLAN.md and PRD.md with completed test and refactoring status ([3dc5ab7](https://github.com/clarity-tech/invoicing/commit/3dc5ab7584a0660ef13c74e44ef1c4c13ccde588))
* update PLAN.md with accurate project completion status ([b0e4c9d](https://github.com/clarity-tech/invoicing/commit/b0e4c9d1caae55cfec20353592eb0ebfcb182ae2))
* update PLAN.md with implementation completion status ([076274b](https://github.com/clarity-tech/invoicing/commit/076274b92120e17a3fe5f0f0d4992a0a9f5f0042))
* update PRD to reflect all critical, high, and medium bugs resolved ([7e286b8](https://github.com/clarity-tech/invoicing/commit/7e286b834acf6c21095fbaf84450676c4bd136ef))
* update project documentation and add stakeholder presentation materials ([b0e4c9d](https://github.com/clarity-tech/invoicing/commit/b0e4c9d1caae55cfec20353592eb0ebfcb182ae2))
* update project documentation and planning files ([9fc6f49](https://github.com/clarity-tech/invoicing/commit/9fc6f49f8a8433956d406d790ccd8ecf858d28a4))
* update project documentation to reflect current state ([097e441](https://github.com/clarity-tech/invoicing/commit/097e44128a7d967e627cdf326a57e032153a0914))
* update README and CLAUDE.md for the Cloud Run deployment ([215cd8c](https://github.com/clarity-tech/invoicing/commit/215cd8c531c9252ec1f6f47bf636e40c7d245ab3))
* update README to reflect Clarity Invoicing Application ([ab7311b](https://github.com/clarity-tech/invoicing/commit/ab7311b31f2b7089d38a36763e0af0876be8a7e4))
* update README with production Docker and H5BP information ([ade6188](https://github.com/clarity-tech/invoicing/commit/ade61880d3bc87d27d767575b13062f72fe6c93b))


### Build & CI

* add browser smoke tests to CI pipeline ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* add CI test workflow and fix existing workflow issues ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* add CI workflow and upgrade all GitHub Actions to latest versions ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* add conventional-commit releases with version-tagged images ([4077109](https://github.com/clarity-tech/invoicing/commit/4077109849f67efa855566f947fb57b522772816))
* add production image (FrankenPHP + Octane) test pipeline ([cf238b2](https://github.com/clarity-tech/invoicing/commit/cf238b29ae0a65294ff66ec81765c3eea27787c4))
* added env ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* allow the release workflow to be dispatched manually ([4077109](https://github.com/clarity-tech/invoicing/commit/4077109849f67efa855566f947fb57b522772816))
* deploy to Cloud Run from the production workflow ([e53bc46](https://github.com/clarity-tech/invoicing/commit/e53bc46c011d980a8bd1a5b280d603e81443e919))
* fix build ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* fix build ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* fix build pipeline ([1936180](https://github.com/clarity-tech/invoicing/commit/19361800142f6dcd8eefb2c8795f374b9af251f5))
* fix checks failures from May changes ([0977577](https://github.com/clarity-tech/invoicing/commit/0977577dfdba4df6f3176802ff4bce812ba9595b))
* keep the weekly Sail image validation ([e487ac1](https://github.com/clarity-tech/invoicing/commit/e487ac16203c5632747ea818cc1d026c73083459))
* **test-frontend:** generate Wayfinder routes before Vitest ([0977577](https://github.com/clarity-tech/invoicing/commit/0977577dfdba4df6f3176802ff4bce812ba9595b))
