/**
 * Custom search dropdown for SearchDesign module.
 */
define([
    'jquery',
    'underscore',
    'mage/template',
    'matchMedia',
    'jquery-ui-modules/widget',
    'jquery-ui-modules/core',
    'mage/translate'
], function ($, _, mageTemplate, mediaCheck) {
    'use strict';

    function isEmpty(value) {
        return value.length === 0 || value == null || /^\s+$/.test(value);
    }

    $.widget('mage.quickSearch', {
        options: {
            autocomplete: 'off',
            minSearchLength: 3,
            responseFieldElements: '.search-suggestion-item',
            selectClass: 'selected',
            submitBtn: 'button[type="submit"]',
            searchLabel: '[data-role=minisearch-label]',
            clearBtn: '.search-clear',
            isExpandable: null,
            suggestionDelay: 300
        },

        _create: function () {
            this.responseList = {
                indexList: null,
                selected: null
            };
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = this.searchForm.find(this.options.searchLabel);
            this.clearBtn = this.searchForm.find(this.options.clearBtn);
            this.isExpandable = this.options.isExpandable;

            _.bindAll(this, '_onKeyDown', '_onPropertyChange', '_onSubmit');

            this.submitBtn.disabled = true;

            this.element.attr('autocomplete', this.options.autocomplete);

            mediaCheck({
                media: '(max-width: 768px)',
                entry: function () {
                    this.isExpandable = true;
                }.bind(this),
                exit: function () {
                    this.isExpandable = false;
                }.bind(this)
            });

            this.searchLabel.on('click', function (e) {
                if (this.isExpandable && this.isActive()) {
                    e.preventDefault();
                }
            }.bind(this));

            this.element.on('blur', $.proxy(function () {
                if (!this.searchLabel.hasClass('active')) {
                    return;
                }

                setTimeout($.proxy(function () {
                    if (this.autoComplete.is(':hidden')) {
                        this.setActiveState(false);
                    } else {
                        this.element.trigger('focus');
                    }
                    this.autoComplete.hide();
                    this._updateAriaHasPopup(false);
                }, this), 250);
            }, this));

            if (this.element.get(0) === document.activeElement) {
                this.setActiveState(true);
            }

            this.element.on('focus', this.setActiveState.bind(this, true));
            this.element.on('keydown', this._onKeyDown);
            this.element.on('input propertychange', _.debounce(this._onPropertyChange, this.options.suggestionDelay));

            this.searchForm.on('submit', $.proxy(function (e) {
                this._onSubmit(e);
                this._updateAriaHasPopup(false);
            }, this));

            this.autoComplete.on('click', '.search-product-cart .action.tocart', function (e) {
                var button = $(e.currentTarget),
                    cartWrap = button.closest('.search-product-cart'),
                    actionUrl = cartWrap.data('action'),
                    qty = cartWrap.find('input[name="qty"]').val() || 1,
                    formKey = cartWrap.find('input[name="form_key"]').val() || '',
                    postForm;

                e.preventDefault();

                if (!actionUrl) {
                    return;
                }

                postForm = $('<form>', {
                    method: 'post',
                    action: actionUrl,
                    style: 'display:none;'
                });

                postForm.append($('<input>', {
                    type: 'hidden',
                    name: 'form_key',
                    value: formKey
                }));

                postForm.append($('<input>', {
                    type: 'hidden',
                    name: 'qty',
                    value: qty
                }));

                $('body').append(postForm);
                postForm.trigger('submit');
            });

            this.clearBtn.on('click', function () {
                this.element.val('');
                this.clearBtn.removeClass('is-visible');
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.trigger('focus');
            }.bind(this));
        },

        isActive: function () {
            return this.searchLabel.hasClass('active');
        },

        setActiveState: function (isActive) {
            var searchValue;

            this.searchForm.toggleClass('active', isActive);
            this.searchLabel.toggleClass('active', isActive);

            if (this.isExpandable) {
                this.element.attr('aria-expanded', isActive);
                searchValue = this.element.val();
                this.element.val('');
                this.element.val(searchValue);
            }
        },

        _getFirstVisibleElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.first() : false;
        },

        _getLastElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.last() : false;
        },

        _updateAriaHasPopup: function (show) {
            if (show) {
                this.element.attr('aria-haspopup', 'true');
            } else {
                this.element.attr('aria-haspopup', 'false');
            }
        },

        _resetResponseList: function (all) {
            this.responseList.selected = null;

            if (all === true) {
                this.responseList.indexList = null;
            }
        },

        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }

            if (this.responseList.selected) {
                this.element.val(this.responseList.selected.find('.search-suggestion-text').text());
            }
        },

        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            switch (keyCode) {
                case $.ui.keyCode.HOME:
                    if (this._getFirstVisibleElement()) {
                        this._getFirstVisibleElement().addClass(this.options.selectClass);
                        this.responseList.selected = this._getFirstVisibleElement();
                    }
                    break;

                case $.ui.keyCode.END:
                    if (this._getLastElement()) {
                        this._getLastElement().addClass(this.options.selectClass);
                        this.responseList.selected = this._getLastElement();
                    }
                    break;

                case $.ui.keyCode.ESCAPE:
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    break;

                case $.ui.keyCode.ENTER:
                    if (this.element.val().length >= parseInt(this.options.minSearchLength, 10)) {
                        this.searchForm.trigger('submit');
                        e.preventDefault();
                    }
                    break;

                case $.ui.keyCode.DOWN:
                    if (this.responseList.indexList) {
                        if (!this.responseList.selected) {
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        } else if (!this._getLastElement().hasClass(this.options.selectClass)) {
                            this.responseList.selected = this.responseList.selected
                                .removeClass(this.options.selectClass).next().addClass(this.options.selectClass);
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        }
                        this.element.val(this.responseList.selected.find('.search-suggestion-text').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                        this._updateAriaHasPopup(true);
                        this.autoComplete.show();
                    }
                    break;

                case $.ui.keyCode.UP:
                    if (this.responseList.indexList !== null) {
                        if (!this._getFirstVisibleElement().hasClass(this.options.selectClass)) {
                            this.responseList.selected = this.responseList.selected
                                .removeClass(this.options.selectClass).prev().addClass(this.options.selectClass);

                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getLastElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getLastElement();
                        }
                        this.element.val(this.responseList.selected.find('.search-suggestion-text').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                        this._updateAriaHasPopup(true);
                        this.autoComplete.show();
                    }
                    break;
                default:
                    return true;
            }
        },

        _renderDropdown: function (data) {
            var suggestionsHtml = '',
                categoriesHtml = '',
                productsHtml = '',
                i;

            if (data.suggestions && data.suggestions.length) {
                for (i = 0; i < data.suggestions.length; i++) {
                    suggestionsHtml +=
                        '<li class="search-suggestion-item" id="qs-option-' + i + '" role="option">' +
                            '<span class="search-suggestion-text">' + _.escape(data.suggestions[i].text) + '</span>' +
                        '</li>';
                }
            } else {
                suggestionsHtml = '<li class="search-empty">' + $.mage.__('No suggestions') + '</li>';
            }

            if (data.categories && data.categories.length) {
                for (i = 0; i < data.categories.length; i++) {
                    var category = data.categories[i];
                    var parentText = category.parent_name ?
                        ' <span class="search-category-in">' + $.mage.__('in') + '</span> ' + _.escape(category.parent_name) :
                        '';
                    categoriesHtml +=
                        '<li>' +
                            '<a href="' + _.escape(category.url) + '" class="search-category-link">' +
                                '<span class="search-category-name">' + _.escape(category.name) + '</span>' +
                                parentText +
                            '</a>' +
                        '</li>';
                }
            } else {
                categoriesHtml = '<li class="search-empty">' + $.mage.__('No categories') + '</li>';
            }

            if (data.products && data.products.length) {
                for (i = 0; i < data.products.length; i++) {
                    var product = data.products[i];
                    var actionHtml;

                    if (product.can_add_to_cart) {
                        actionHtml =
                            '<div class="search-product-cart" data-action="' + _.escape(product.add_to_cart_url) + '">' +
                                '<input type="hidden" name="form_key" value="' + _.escape(data.form_key) + '">' +
                                '<div class="search-product-qty">' +
                                    '<label>' + $.mage.__('Qty') + '</label>' +
                                    '<input type="number" name="qty" min="1" value="1">' +
                                '</div>' +
                                '<button type="button" class="action tocart">' + $.mage.__('Add to Cart') + '</button>' +
                            '</div>';
                    } else {
                        actionHtml =
                            '<a class="action view" href="' + _.escape(product.url) + '">' + $.mage.__('View') + '</a>';
                    }

                    productsHtml +=
                        '<div class="search-product">' +
                            '<a class="search-product-image" href="' + _.escape(product.url) + '">' +
                                '<img src="' + _.escape(product.image) + '" alt="' + _.escape(product.name) + '" />' +
                            '</a>' +
                            '<div class="search-product-info">' +
                                '<a class="search-product-name" href="' + _.escape(product.url) + '">' + _.escape(product.name) + '</a>' +
                                '<div class="search-product-sku">' + $.mage.__('Item #') + ' ' + _.escape(product.sku) + '</div>' +
                                (product.price_html ? '<div class="search-product-price">' + product.price_html + '</div>' : '') +
                            '</div>' +
                            '<div class="search-product-actions">' + actionHtml + '</div>' +
                        '</div>';
                }
            } else {
                productsHtml = '<div class="search-empty">' + $.mage.__('No products found') + '</div>';
            }

            return (
                '<div class="search-design-dropdown" role="listbox">' +
                    '<div class="search-design-left">' +
                        '<div class="search-section">' +
                            '<div class="search-section-title">' + $.mage.__('Suggestions') + '</div>' +
                            '<ul class="search-suggestions">' + suggestionsHtml + '</ul>' +
                        '</div>' +
                        '<div class="search-section">' +
                            '<div class="search-section-title">' + $.mage.__('Product Categories') + '</div>' +
                            '<ul class="search-categories">' + categoriesHtml + '</ul>' +
                        '</div>' +
                    '</div>' +
                    '<div class="search-design-right">' +
                        '<div class="search-recommend-header">' +
                            '<div class="search-recommend-title">' + $.mage.__('Recommended Products for') + '</div>' +
                            '<div class="search-recommend-query">' + _.escape(data.query) + '</div>' +
                            '<a class="search-shop-all" href="' + _.escape(data.search_url) + '">' + $.mage.__('Shop All') + '</a>' +
                        '</div>' +
                        '<div class="search-recommend-list">' + productsHtml + '</div>' +
                    '</div>' +
                '</div>'
            );
        },

        _onPropertyChange: function () {
            var searchField = this.element,
                clonePosition = {
                    position: 'absolute',
                    width: this.searchForm.outerWidth()
                },
                value = this.element.val();

            this.submitBtn.disabled = true;
            if (value.length) {
                this.clearBtn.addClass('is-visible');
            } else {
                this.clearBtn.removeClass('is-visible');
            }

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                this.submitBtn.disabled = false;

                if (this.options.url !== '') {
                    $.getJSON(this.options.url, { q: value }, $.proxy(function (data) {
                        if (data && (data.suggestions || data.categories || data.products)) {
                            var dropdownHtml = this._renderDropdown(data);

                            this._resetResponseList(true);

                            this.responseList.indexList = this.autoComplete
                                .html(dropdownHtml)
                                .css(clonePosition)
                                .show()
                                .find(this.options.responseFieldElements + ':visible');

                            this.element.removeAttr('aria-activedescendant');

                            if (this.responseList.indexList.length) {
                                this._updateAriaHasPopup(true);
                            } else {
                                this._updateAriaHasPopup(false);
                            }

                            this.responseList.indexList
                                .on('click', function (e) {
                                    this.responseList.selected = $(e.currentTarget);
                                    this.searchForm.trigger('submit');
                                }.bind(this))
                                .on('mouseenter mouseleave', function (e) {
                                    this.responseList.indexList.removeClass(this.options.selectClass);
                                    $(e.target).addClass(this.options.selectClass);
                                    this.responseList.selected = $(e.target);
                                    this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                                }.bind(this));
                        } else {
                            this._resetResponseList(true);
                            this.autoComplete.hide();
                            this._updateAriaHasPopup(false);
                            this.element.removeAttr('aria-activedescendant');
                        }
                    }, this));
                }
            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        }
    });

    return $.mage.quickSearch;
});
