<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyClickSendSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Clicksend don't do any webhook signing :-(
        // Instead we will just verify we ignore requests that aren't for this server
        //        if ($request->input('
        $incomingSubaccountId = $request->input('subaccount_id');

        // Check if the subaccount_id exists and is not empty.
        if (empty($incomingSubaccountId)) {
            return response()->json(['error' => 'Unauthorized: Missing subaccount ID'], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the expected subaccount_id from the config.
        $configSubaccountId = config('services.clicksend.subaccount_id');

        // Reject the request if the subaccount IDs don't match.
        if ($incomingSubaccountId !== $configSubaccountId) {
            return response()->json(['error' => 'Unauthorized: Subaccount ID mismatch'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
