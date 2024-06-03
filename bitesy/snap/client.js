document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("bayar").addEventListener("click", function () {
    const transactionId = "{{ $transaksi->id }}";

    fetch("/api/initiate-payment", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": "{{ csrf_token() }}",
      },
      body: JSON.stringify({ transaction_id: transactionId }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.snap_token) {
          snap.pay(data.snap_token, {
            onSuccess: function (result) {
              console.log(result);
              handlePaymentResult(result);
            },
            onPending: function (result) {
              console.log(result);
              handlePaymentResult(result);
            },
            onError: function (result) {
              console.log(result);
              handlePaymentResult(result);
            },
          });
        } else {
          console.error("Failed to get snap token", data);
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  function handlePaymentResult(result) {
    fetch("/api/handle-notification", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": "{{ csrf_token() }}",
      },
      body: JSON.stringify({
        transaction_id: "{{ $transaksi->id }}",
        json: JSON.stringify(result),
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
      })
      .catch((error) => console.error("Error:", error));
  }
});
