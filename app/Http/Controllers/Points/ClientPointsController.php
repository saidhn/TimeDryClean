<?php

namespace App\Http\Controllers\Points;

use App\Http\Controllers\Controller;
use App\Models\PointsPackage;
use App\Models\User;
use App\Models\UserPointsPackage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientPointsController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Client: list available packages to buy.
     */
    public function index()
    {
        $packages = PointsPackage::active()->orderBy('points')->get();
        $client   = Auth::user();

        $history = UserPointsPackage::where('user_id', $client->id)
            ->with('pointsPackage')
            ->latest()
            ->paginate(10);

        return view('points.client.index', compact('packages', 'client', 'history'));
    }

    /**
     * Client: initiate KNET purchase for a package.
     */
    public function buy(Request $request)
    {
        $request->validate([
            'points_package_id' => 'required|exists:points_packages,id',
        ]);

        $package = PointsPackage::active()->findOrFail($request->points_package_id);
        $client  = Auth::user();

        DB::beginTransaction();
        try {
            // Create a pending purchase record
            $purchase = UserPointsPackage::create([
                'user_id'           => $client->id,
                'points_package_id' => $package->id,
                'points_awarded'    => $package->points,
                'price_paid_kwd'    => $package->price_kwd,
                'payment_method'    => 'knet',
                'status'            => 'pending',
            ]);

            DB::commit();

            // Redirect to KNET payment using existing payment infrastructure
            // We piggyback the existing KnetService; the callback will call completePointsPurchase
            $knetService = app(\App\Services\KnetService::class);
            $result = $knetService->createPointsPackagePayment(
                (float) $package->price_kwd,
                $client->id,
                $purchase->id
            );

            if ($result['status'] === 'success') {
                // Update purchase with transaction id
                $purchase->update(['transaction_id' => $result['tracking_id']]);
                return redirect($result['payment_uri']);
            }

            DB::beginTransaction();
            $purchase->update(['status' => 'failed']);
            DB::commit();

            return back()->withErrors(['error' => __('messages.payment_initiation_failed')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Points package purchase error: ' . $e->getMessage());
            return back()->withErrors(['error' => __('messages.order_error_try_again')]);
        }
    }

    /**
     * Complete a KNET-paid points purchase (called from KnetService callback).
     */
    public function completePurchase(UserPointsPackage $purchase): void
    {
        if ($purchase->status === 'completed') {
            return;
        }

        DB::transaction(function () use ($purchase) {
            $purchase->update(['status' => 'completed']);
            $purchase->user->increment('points_balance', $purchase->points_awarded);
        });

        $this->notificationService->sendTransactionNotification(
            $purchase->user,
            'points_added',
            ['points' => $purchase->points_awarded, 'points_balance' => $purchase->user->fresh()->points_balance]
        );
    }

    /**
     * Employee/Admin: show form to manually add a package to a user.
     */
    public function assignForm()
    {
        $packages = PointsPackage::active()->get();

        return view('points.admin.assign', compact('packages'));
    }

    /**
     * Employee/Admin: manually assign a points package to a user.
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'user_id'           => 'required|exists:users,id',
            'points_package_id' => 'required|exists:points_packages,id',
        ]);

        $package = PointsPackage::findOrFail($validated['points_package_id']);
        $user    = User::findOrFail($validated['user_id']);

        DB::transaction(function () use ($package, $user) {
            UserPointsPackage::create([
                'user_id'           => $user->id,
                'points_package_id' => $package->id,
                'points_awarded'    => $package->points,
                'price_paid_kwd'    => 0,
                'payment_method'    => 'manual',
                'status'            => 'completed',
                'added_by'          => Auth::id(),
            ]);

            $user->increment('points_balance', $package->points);
        });

        return redirect()->back()
            ->with('success', __('messages.points_assigned_successfully', ['points' => $package->points, 'user' => $user->name]));
    }

    /**
     * Admin/Employee: view all purchase history.
     */
    public function history(Request $request)
    {
        $purchases = UserPointsPackage::with('user', 'pointsPackage', 'addedBy')
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', '%' . $request->search . '%'));
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('points.admin.history', compact('purchases'));
    }
}
