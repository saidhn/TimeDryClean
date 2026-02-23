<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KNET Test Gateway</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); min-height: 100vh; display: flex; align-items: center; }
        .gateway-card { max-width: 450px; margin: auto; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,.25); }
        .knet-header { background: #004d40; color: white; padding: 20px; border-radius: 16px 16px 0 0; text-align: center; }
        .knet-header h4 { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card gateway-card">
            <div class="knet-header">
                <h4>KNET Test Payment Gateway</h4>
                <small>This is a test environment</small>
            </div>
            <div class="card-body p-4">
                <div class="mb-3 p-3 bg-light rounded">
                    <p class="mb-1"><strong>Amount:</strong> {{ number_format($payment->amount, 3) }} KWD</p>
                    <p class="mb-0"><strong>Tracking ID:</strong> {{ $trackingId }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" value="888888 0000000001" readonly>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Expiry</label>
                        <input type="text" class="form-control" value="09/25" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">PIN</label>
                        <input type="password" class="form-control" value="1234" readonly>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <form action="{{ route('client.payment.callback') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tracking_id" value="{{ $trackingId }}">
                        <input type="hidden" name="result" value="CAPTURED">
                        <input type="hidden" name="paymentid" value="TEST-{{ time() }}">
                        <input type="hidden" name="tranid" value="TXN-{{ time() }}">
                        <button type="submit" class="btn btn-success btn-lg w-100 mb-2">
                            Confirm Payment (Success)
                        </button>
                    </form>

                    <form action="{{ route('client.payment.callback') }}" method="POST">
                        @csrf
                        <input type="hidden" name="tracking_id" value="{{ $trackingId }}">
                        <input type="hidden" name="result" value="FAILED">
                        <input type="hidden" name="paymentid" value="TEST-{{ time() }}">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            Cancel / Decline
                        </button>
                    </form>
                </div>

                <p class="text-muted small text-center mt-3">
                    This simulates the KNET payment gateway for testing purposes.
                    In production, users will be redirected to the real KNET gateway.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
