# Public pages and authentication

The landing page introduces JCI Carmona; JCISTEM is the chapter's member portal. Membership inquiries go to the chapter or JCI Philippines. Portal accounts are provided by the chapter administrator, with access determined by role.

Shared organization links live in `config/chapter.php`. The public header identity is in `resources/views/shared/public-brand.blade.php`. Both landing and authentication screens use `public/assets/css/public.css`; their page styles are separate from dashboard styles.

The public footer is in `resources/views/shared/public-footer.blade.php`. It follows the JCI reference's light background, larger identity, three link columns, social area, and subtle shield watermark. The left column directs visitors to chapter updates; there is no newsletter submission or app download service in this system. All member links use the application's routes so workspace URLs are preserved.

Authentication remains native to JCISTEM (username/email and password). Facebook links on the landing page are optional external links, never a sign-in provider; authentication-page footer links stay on this website. Public photographs are local assets and do not require a Facebook account, embed, or SDK. Login and recovery share equal desktop panel widths, a 400px maximum form width, and 50px fields and action buttons. Small screens retain natural page scrolling and validation messages can expand the form.

## Content sources

Reviewed on 19 October 2026:

- [JCI Worldwide](https://jci.cc/): JCI blue, navy and teal visual direction; four areas of opportunity. Descriptions are summaries, not claims about specific chapter programs.
- [About JCI](https://jci.cc/about/): the shared JCI mission is quoted and linked on the landing page.
- [JCI Philippines membership inquiries](https://jciphilippines.com/get-involved/join-us/): the national form refers prospective members to a local chapter.
- [Manila Bulletin, 2 February 2026](https://mb.com.ph/2026/02/02/carmona-lgu-rolls-out-literacy-project-for-out-of-school-children-youth): identifies JCI Carmona as one of the partners in the city-led Project LAPIS. It does not establish that every volunteer pictured belongs to JCI. The image and captions describe the project, with photo attribution to Leonard Danniel M. Diaz / Manila Bulletin.
- [PNVSCA, 31 July 2026](https://pnvsca.gov.ph/?p=9684): reports JCI Carmona's participation in the CALABARZON International Volunteer Year forum on 14 July. Forum attendance figures are not presented as chapter membership or chapter impact statistics. The blue forum panel is a typographic illustration, not an event photograph.
- [JCI Carmona Facebook](https://www.facebook.com/jcicarmonaofficial): the existing URL was confirmed by the user. The search tool was throttled; the public page subsequently loaded in a fresh browser without signing in. The chapter's Bags2School post, shared on 20 August 2026, describes an activity held on 27 June 2026 at Talinguroy Elementary School, La Trinidad, Benguet. JCI Carmona participated as a partner host. This is not described as an event held in Carmona.
- [Original Bags2School post](https://www.facebook.com/jcicarmonaofficial/posts/pfbid0zduN6nJyszDVyptR5eqBb62BCj2UkkQHCHwsJCKL3aDMA3h9cSE8FwrbseaTQJZml): three publicly displayed photographs are stored as `bags2school-group.jpg` (photo ID 122180965244393816), `bags2school-preparation.jpg` (122180965262393816), and `bags2school-activity.jpg` (122180965268393816). Each is the unaltered 586 × 414 image served by Facebook; embedded credits and full image proportions are preserved. The gallery adds explicit attribution and an optional original-post link. It makes no runtime request to Facebook and requires no Facebook login. No video was selected because a suitable public video could not be verified.

The JCI primary logo was downloaded from the existing JCI Europe asset URL: https://jcieurope.cc/wp-content/smush-webp/2025/12/New_JCI_Logo_Primary-1-scaled.png.webp . The original JCISTEM symbol remains in use for portal identification. Images are served locally; fonts have system fallbacks.

## Behavior and verification

- Native disclosure elements keep FAQs and mobile navigation usable without JavaScript. JavaScript adds outside-click, Escape, and selection dismissal for mobile navigation.
- Passwords are masked by default. Show/hide controls appear only when JavaScript is available. They expose their target and pressed state to assistive technology.
- Authentication fields have explicit labels, autocomplete, validation messages and error associations. Password values are never repopulated.
- Session replacement is optional and explains that the previous sign-in will end. Existing authentication and authorization rules remain in the controller.
- Recovery describes all three existing steps: request a code, verify it, choose a password. Workspace paths are preserved by the application's existing URL middleware.
- Recovery email delivery depends on the configured mail transport. The local environment currently uses `MAIL_MAILER=log`, so it logs messages instead of sending them. Configure a working mail transport before expecting inbox delivery.
- Run focused feature checks with `php artisan test --compact --filter="PublicEntryTest|ExampleTest|LogoutTest"` and compile views with `php artisan view:cache`.
