/**
 * citrus plugin for Craft CMS
 *
 * citrus JS
 *
 * @author    Whitespace
 * @copyright Copyright (c) 2018 Whitespace
 * @link      https://whitespacers.com
 * @package   Citrus
 * @since     0.0.1
 */

(function($, undefined) {
	var Citrus, PurgeBan, Modals;

	Citrus = function() {
		this.init();
	}

	Citrus.prototype.init = function () {
		$('form.purgeban').each(function () {
			new PurgeBan(this, $('#purgeban-output .output'));
		});

		$('.prefix-reveal[data-prefix-reveal]').each(function () {
			var $this = $(this);
			new PrefixReveal($this, $('#' + $this.data('prefix-reveal')));
		});

		$('.ajax-form').each(function() {
			new AjaxForm($(this));
		});

		new Modals();
	}

	PurgeBan = function(form, $output) {
		this.$form = $(form);
		this.$output = $output;
		this.$form.submit($.proxy(this.submit, this));
	}

	PurgeBan.prototype.submit = function(event) {
		event.preventDefault();

		this.$output.html('');

		$.post(this.$form.attr('action'), this.$form.serialize())
			.then($.proxy(function(response) {
				// Update output
				var message = '';

				if (response.responses) {
					response.responses.forEach(function(response) {
						message += response.message + '\n';
					});
				}

				this.$output.html(
					response.query + '\n\n' +
					message
				);

				// Update CSRF token
				this.$form.find('input[name=\'' + response.CSRF.name + '\']')
					.val(response.CSRF.value);
			}, this));
	}

	Modals = function () {
		this.modals = {};

		// Set up cancel buttons
		$('[data-form-cancel]').click($.proxy(function (event) {
			event.preventDefault();
			this.close($(event.target).closest('.modal'));
		}, this));

		// Set up trigger buttons
		$('[data-modal-trigger]').click($.proxy(function(event) {
			var id = $(event.target).data('modal-trigger'),
				$element = $('#' + id);

			event.preventDefault();

			if ($element.length) {
				if (!this.modals[id]) {
					this.modals[id] = {
						$element: $element,
						modal: new Garnish.Modal($element)
					};
				} else {
					this.open(id);
				}
			}
		}, this));
	}

	Modals.prototype.open = function (id) {
		if (this.modals[id]) {
			this.modals[id].modal.show();
		}
	}

	Modals.prototype.close = function($modal) {
		var id = $modal.attr('id');

		if (this.modals[id]) {
			this.modals[id].modal.hide();
		}
	}

	PrefixReveal = function($trigger, $target) {
		$trigger.click(function(event) {
			event.preventDefault();
			$target.toggleClass('revealed');
		});
	}

	AjaxForm = function($form) {
		this.$output = $($form.data('output'));
		this.$form = $form;
		this.$form.submit($.proxy(this.submit, this));
	}

	AjaxForm.prototype.submit = function(event) {
		event.preventDefault();

		this.$output.html('');

		$.post(this.$form.attr('action'), this.$form.serialize())
			.then($.proxy(function (response) {
				this.$output.html(response);
			}, this));
	}

	new Citrus();
}(window.jQuery));
