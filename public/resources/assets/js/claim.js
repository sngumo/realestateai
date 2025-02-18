$(function () {
    if (typeof SITE_PATH === 'undefined')
        return;
    PNotify.prototype.options.styling = "fontawesome";
    var stack_modal = {"dir1": "down", "dir2": "right", "push": "top", "modal": true, "overlay_close": true};
    var url = SITE_PATH + "/ajax/admin/policies/getcustomer";
    var $select_elem = $(".chosen-select");
    var $submit_btn = $('#btnsubmit');
    var $policy_preview = $('#policy_preview');
    var $quote_info = $('.select-quote');
    var $claim_form = $('#claim-details');

    $submit_btn.hide();
    var Claim = {
        initCustomerSelect: function () {
            $claim_form.hide();
            $select_elem.empty();
            $select_elem.select2({
                minimumInputLength: 2,
                theme: "bootstrap",
                placeholder: "Select a customer",
                allowClear: true,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            query: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    },
                    cache: true
                }
            });
            $select_elem.on('select2:select', function () {
                Claim.selectCustomer(this.value);
            });
            $(document).on('change', '#policies', function () {
                Claim.selectPolicy();
            });
            $(document).on('click', '#proceed', function () {
                $(this).hide();
                new PNotify({text: "Please enter claim details", type: 'info'});
                $claim_form.show();
            });
        },
        selectCustomer: function (selected) {
            $.ajax({
                url: SITE_PATH + "/ajax/admin/claims/getpolicies?id=" + selected,
                type: 'GET',
                dataType: 'json',
                beforeSend: function () {
                    $quote_info.html(PRELOADER2);
                    $policy_preview.html('');
                },
                success: function (response) {
                    if (response.status) {
                        $('#embedded_quote').html('');
                        $quote_info.html(
                            '<label id=\"label_quote\" for=\"select-quote\">Select a policy to use for the New Claim</label>' + response.content
                        );
                        $('input[name=customer_id]').val(selected);
                        if (typeof POLICY_ID !== 'undefined' && POLICY_ID) {
                            $(document).find('#policies').val(POLICY_ID).trigger('change');
                        }
                        // new PNotify({text: "Now select policy", type: 'info'});
                    } else {
                        var customer = null;
                        try {
                            customer = $select_elem.select2('data')[0].text;
                        }
                        catch (e) {
                            customer = "Selected Customer";
                        }
                        $quote_info.html('');
                        $select_elem.val(null).trigger("change");
                        new PNotify({
                            // title: 'Warning!',
                            text: 'No policies found for the <strong>' + customer + '</strong>',
                            type: 'error',
                            stack: stack_modal,
                            addclass: "stack-modal"
                        });
                    }
                }
            });
        },
        selectPolicy: function () {
            var policy = $('#policies').val();
            if (policy === '') {
                new PNotify({text: "Please select policy", type: 'info'});
                return;
            }
            $.ajax({
                url: SITE_PATH + "/ajax/admin/claims/getpolicypreview?id=" + policy,
                type: 'GET',
                beforeSend: function () {
                    $claim_form.hide();
                    $policy_preview.html(PRELOADER2);
                },
                success: function (response) {
                    $policy_preview.html(response);
                    $submit_btn.show();
                    $('input[name=policy_id]').val(policy);
                    console.log(policy);
                }
            });
        }
    };
    Claim.initCustomerSelect();
    if ($('input[name=customer_id]').val()) {
        customer = $('input[name=customer_id]').val();
        Claim.selectCustomer(customer);
    }
});