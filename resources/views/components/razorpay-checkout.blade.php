<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    function openRazorpayCheckout(config) {
        var options = {
            key: config.razorpay_key,
            amount: config.amount,
            currency: "INR",
            name: "{{ config('app.name') }}",
            description: config.description || "Membership Payment",
            image: "/logo.png",
            order_id: config.order_id || undefined,
            subscription_id: config.subscription_id || undefined,
            recurring: config.subscription_id ? 1 : 0,
            handler: function(response) {
                fetch('/payment/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(response)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Payment verification failed. Contact support.');
                    }
                });
            },
            prefill: {
                name: config.member_name,
                email: config.member_email,
                contact: config.member_phone
            },
            theme: { color: "#ff6b35" }
        };
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function(response) {
            console.error(response.error);
            alert('Payment failed: ' + response.error.description);
        });
        rzp.open();
    }
</script>
