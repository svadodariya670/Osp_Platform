$(document).ready(function () {

    // Default validation settings
    $.validator.setDefaults({
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback',
        errorPlacement: function (error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        }
    });

    // Reusable function for AJAX form submission
    function handleAjaxForm(formSelector, backendUrl) {
        $(formSelector).validate({
            submitHandler: function (form) {
                $.ajax({
                    url: backendUrl,
                    type: "POST",
                    data: $(form).serialize(),
                    beforeSend: function () {
                        $("#formMessage").html('<div class="alert alert-info">Processing...</div>');
                    },
                    success: function (response) {
                        $("#formMessage").html('<div class="alert alert-success">' + response + '</div>');
                        $(form)[0].reset();
                        $(".is-valid").removeClass("is-valid");
                    },
                    error: function () {
                        $("#formMessage").html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
                    }
                });
                return false; // Prevent default form submit
            }
        });
    }

    // Visitor Registration
    $('#visitorRegisterForm').validate({
        rules: {
            name: { required: true, minlength: 3 },
            email: { required: true, email: true },
            mobile: { required: true, digits: true, minlength: 10, maxlength: 15 },
            password: { required: true, minlength: 6 },
            confirm_password: { required: true, equalTo: '#password' }
        },
        messages: {
            confirm_password: { equalTo: "Passwords do not match" }
        }
    });
    handleAjaxForm("#visitorRegisterForm", "process_register.php");

    // Main Login
    $('#mainLoginForm').validate({
        rules: {
            role: { required: true },
            email: { required: true, email: true },
            password: { required: true }
        }
    });

    // Contact Form
    $('#contactForm').validate({
        rules: {
            name: { required: true, minlength: 3 },
            email: { required: true, email: true },
            message: { required: true, minlength: 10 }
        }
    });
    handleAjaxForm("#contactForm", "process_contact.php");

    // Customer Book Service
    $('#customerBookForm').validate({
        rules: {
            service: { required: true },
            date: { required: true, date: true },
            details: { required: true, minlength: 10 }
        }
    });
    handleAjaxForm("#customerBookForm", "process_booking.php");

    // Customer Review
    $('#customerReviewForm').validate({
        rules: {
            service: { required: true },
            rating: { required: true },
            comment: { required: true, minlength: 5 }
        }
    });
    handleAjaxForm("#customerReviewForm", "process_review.php");

    // Provider Service Form
    $('#providerServiceForm').validate({
        rules: {
            service_title: { required: true, minlength: 3 },
            service_category: { required: true },
            service_description: { required: true, minlength: 10 }
        }
    });

    // Provider Subscription
    $('#providerSubscriptionForm').validate({
        rules: { plan: { required: true } }
    });

    // Admin Plan
    $('#adminPlanForm').validate({
        rules: {
            plan_name: { required: true, minlength: 3 },
            plan_price: { required: true, number: true, min: 0 },
            plan_status: { required: true }
        }
    });

    // Admin Profile
    $('#adminProfileForm').validate({
        rules: {
            admin_name: { required: true, minlength: 3 },
            admin_email: { required: true, email: true },
            admin_phone: { required: true, digits: true, minlength: 10, maxlength: 15 }
        }
    });

});
