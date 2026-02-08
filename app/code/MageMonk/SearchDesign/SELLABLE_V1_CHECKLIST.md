# Sellable V1 Checklist (Marketplace-Focused)

## Mandatory Gates
- [ ] `bin/magento setup:upgrade` succeeds in clean environment
- [ ] `bin/magento setup:di:compile` succeeds in production mode
- [ ] `vendor/bin/phpcs --standard=Magento2 app/code/MageMonk/SearchDesign` has zero errors
- [ ] Module can be enabled/disabled without storefront/admin fatal errors

## Functional Quality
- [ ] Search dropdown renders on desktop and mobile
- [ ] Add to Cart from dropdown redirects to correct page (not AJAX endpoint)
- [ ] Long product titles do not break layout
- [ ] Empty states render correctly (no categories/products/suggestions)

## Compatibility
- [ ] Works with default Magento search configuration
- [ ] Works with engine-backed fulltext (as configured in store)
- [ ] No theme-breaking global CSS leaks

## Package Readiness
- [x] `composer.json` present with module metadata/autoload
- [x] `README.md` with install/config usage
- [ ] Release notes/changelog prepared
- [ ] Marketplace listing assets prepared (icons, screenshots, docs)

## Recommended (Strongly)
- [ ] Unit tests for provider/controller logic
- [ ] MFTF smoke flow for search dropdown + add to cart
- [ ] Performance check on larger catalog
