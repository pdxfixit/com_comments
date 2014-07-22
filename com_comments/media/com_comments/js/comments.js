(function () {
    var $ = document.id;

    this.Comments = new Class({
        Extends: Request,
        element: null,
        form: null,

        options: {
            action: '',
            evalScripts: false,

            onComplete: function () {
                if (this.response && this.response.text) {
                    this.element.empty().set('html', this.response.text);

                    this.element.getElementById('rpoohcheck').set('defaultValue', '');

                    new Comments(this.element);
                } else {
                    this.get(this.url);
                }
            }
        },

        initialize: function (element, options) {
            options = options || {};
            this.element = document.id(element);

            var that = this;
            this.element.getElements('a[data-action]').addEvent('click', function (e) {
                if (e.target.get('data-action')) {
                    e.stop();
                    that.execute(this.get('data-action'), this.get('data-id'), e);
                }
            });

            this.form = this.element.getElement('form');
            this.url = this.form.getProperty('action') + '&tmpl=';

            options.url = this.url;
            this.parent(options);

            this.form.addEvent('submit', function (e) {
                e.stop();

                var passed = true,
                    elements = this.form.getElements('[name=comment], [name=captcha_value], [name=email], [name=username]');

                Array.each(elements, function (element) {
                    if (element.value) {
                        element.removeClass('invalid');
                    } else {
                        passed = false;
                        element.addClass('invalid');
                    }
                });

                if (passed) {
                    this.execute('add')
                }
                ;

            }.bind(this));
        },

        execute: function (action, data, event) {
            var method = '_action' + action.capitalize();

            if (typeOf(this[method]) == 'function') {
                this.options.action = action;
                this[method].call(this, data, event);
            }
        },

        _actionReply: function (data, event) {
            var target = $(event.target);
            $$('[name=path]').set('value', data);
            this.form.inject(target.getParent(), 'after');

        },

        _actionUnpublish: function (data, event) {
            if (confirm('Are you sure you want to Unpublish this comment')) {
                this.options.url = [this.options.url, 'id=' + data].join('&');
                this.post({url: this.options.url, enabled: 0, task: 'unpublish', _token: this.form.getElement('[id=_token]').getAttribute('name')});
            }
        },
        _actionPublish: function (data, event) {
            if (confirm('Are you sure you want to publish this comment')) {
                this.options.url = [this.options.url, 'id=' + data].join('&');
                this.post({url: this.options.url, enabled: 1, task: 'publish', _token: this.form.getElement('[id=_token]').getAttribute('name')});
            }
        },
        _actionDelete: function (data, event) {
            if (confirm('Are you sure you want to delete this comment?')) {
                this.options.url = [this.options.url, 'id=' + data].join('&');
                //this.DELETE(this.form);
                this.post({url: this.options.url, task: 'remove', _token: this.form.getElement('[id=_token]').getAttribute('name')});
            }
        },
        _actionSubscribe: function (data, event) {
            this.post({url: this.options.url, row: this.form.getElement('[name=row]').value, table: this.form.getElement('[name=table]').value, task: 'subscribe', _token: this.form.getElement('[id=_token]').getAttribute('name')});

        },
        _actionUnsubscribe: function (data, event) {
            this.post({url: this.options.url, row: this.form.getElement('[name=row]').value, table: this.form.getElement('[name=table]').value, task: 'unsubscribe', _token: this.form.getElement('[id=_token]').getAttribute('name')});

        },
        _actionReport: function (data, event) {
            if (confirm('Report this comment to an administrator?')) {
                this.post({url: this.options.url, comments_comment_id: data, task: 'report', _token: this.form.getElement('[id=_token]').getAttribute('name')});
            }
        },
        _actionSpam: function (data, event) {
            if (confirm('Are you sure you want to mark this comment as spam?')) {
                this.post({url: this.options.url, comments_comment_id: data, task: 'spam', _token: this.form.getElement('[id=_token]').getAttribute('name')});
            }
        },
        _actionAdd: function (data) {
            var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (regex.test(this.form.getElement('[id=email]').value)) {
                this.post(this.form);
            } else {
                // todo: perhaps something more elegant than an alert?
                alert('Please check your email address.');
            }
        }
    });
})();

window.addEvent('domready', function () {
    new Comments('comments');
});
