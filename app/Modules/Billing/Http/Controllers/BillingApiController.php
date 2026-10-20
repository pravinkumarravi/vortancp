<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Modules\Billing\Services\BillingProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class BillingApiController extends Controller
{
    public function __construct(
        protected BillingProvisioningService $provisioningService
    ) {}

    /**
     * Create / provision a new hosting account.
     * POST /api/v1/billing/accounts/create
     */
    public function createAccount(Request $request): JsonResponse
    {
        try {
            $result = $this->provisioningService->createAccount($request->all());
            return response()->json($result, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to provision account: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a hosting account.
     * POST /api/v1/billing/accounts/{username}/suspend
     */
    public function suspendAccount(string $username, Request $request): JsonResponse
    {
        try {
            $reason = (string) $request->input('reason', 'Suspended by billing system');
            $result = $this->provisioningService->suspendAccount($username, $reason);
            return response()->json($result);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsuspend a hosting account.
     * POST /api/v1/billing/accounts/{username}/unsuspend
     */
    public function unsuspendAccount(string $username): JsonResponse
    {
        try {
            $result = $this->provisioningService->unsuspendAccount($username);
            return response()->json($result);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Terminate / delete a hosting account.
     * POST /api/v1/billing/accounts/{username}/terminate
     */
    public function terminateAccount(string $username): JsonResponse
    {
        try {
            $result = $this->provisioningService->terminateAccount($username);
            return response()->json($result);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change / upgrade / downgrade an account's package.
     * POST /api/v1/billing/accounts/{username}/change-package
     */
    public function changePackage(string $username, Request $request): JsonResponse
    {
        try {
            $plan = $request->input('plan_id') ?? $request->input('plan_name');
            if (empty($plan)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'plan_id or plan_name is required.',
                ], 422);
            }

            $result = $this->provisioningService->changePackage($username, $plan);
            return response()->json($result);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieve status and usage metrics of an account.
     * GET /api/v1/billing/accounts/{username}/status
     */
    public function getAccountStatus(string $username): JsonResponse
    {
        try {
            $result = $this->provisioningService->getAccountStatus($username);
            return response()->json($result);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List hosting accounts with pagination.
     * GET /api/v1/billing/accounts
     */
    public function listAccounts(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 50), 100);
        $status = $request->query('status');

        $result = $this->provisioningService->listAccounts($page, $perPage, $status ? (string) $status : null);
        return response()->json($result);
    }

    /**
     * List hosting plans for billing system synchronization.
     * GET /api/v1/billing/plans
     */
    public function listPlans(): JsonResponse
    {
        $result = $this->provisioningService->listPlans();
        return response()->json($result);
    }
}
