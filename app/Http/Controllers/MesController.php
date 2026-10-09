<?php

namespace App\Http\Controllers;

use App\Http\Dialogs\ClientAfterSuccess;
use App\Support\Results\OperationResult;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * = Mes.Web/Controllers/MesControllerBase.cs. Gom thao tác lặp lại của mọi controller giao diện:
 * user hiện tại, dịch lỗi từ Service, toast, trả JSON cho dialog / AJAX.
 */
abstract class MesController extends Controller
{
    /** Khóa flash session mà partials/notification-scripts.blade.php đọc để hiện toast (= TempData). */
    public const SuccessMessageFlashKey = 'mes.success-message';
    public const ErrorMessageFlashKey = 'mes.error-message';

    /** Tên khóa lỗi "đầu form" (= ModelState.AddModelError(string.Empty, ...) + asp-validation-summary). */
    public const SummaryErrorKey = 'summary';

    protected function currentUserAccountId(): int
    {
        return (int) Auth::id();
    }

    protected function currentLanguageCode(): string
    {
        return App::getLocale();
    }

    protected function isDialogRequest(): bool
    {
        return request()->isDialogRequest();
    }

    /** Trả lại View kèm lỗi validation (field) - dialog nhận HTML và hiện lỗi tại chỗ. */
    protected function viewWithValidationErrors(View $view, Validator $validator): View
    {
        return $view->withErrors($validator);
    }

    /** Trả lại View với lỗi nghiệp vụ ở đầu form (AddOperationErrorToModelState). */
    protected function viewWithOperationError(View $view, OperationResult $operationResult): View
    {
        return $view->withErrors([self::SummaryErrorKey => $operationResult->localizedError()]);
    }

    /**
     * Kết quả chuẩn cho action POST, dùng được cả hai cách:
     *   - Request AJAX (form trong dialog / data-mes-ajax-form): JSON { isSuccess, message, afterSuccess }
     *   - Request thường (mở trang trực tiếp): toast flash + redirect (Post-Redirect-Get)
     */
    protected function operationResponse(
        OperationResult $operationResult,
        string $successMessageKey,
        string $redirectUrl,
        ClientAfterSuccess $afterSuccess = ClientAfterSuccess::CloseDialog,
    ): Response {
        if (request()->isAjaxRequest()) {
            return response()->json([
                'isSuccess' => $operationResult->isSuccess,
                'message' => $operationResult->isSuccess ? localize($successMessageKey) : $operationResult->localizedError(),
                'afterSuccess' => $afterSuccess->value,
            ]);
        }

        session()->flash(
            $operationResult->isSuccess ? self::SuccessMessageFlashKey : self::ErrorMessageFlashKey,
            $operationResult->isSuccess ? localize($successMessageKey) : $operationResult->localizedError());

        return redirect($redirectUrl);
    }

    /** JSON chuẩn cho request AJAX thuần (Mes.postJson): { isSuccess, message }. */
    protected function operationJson(OperationResult $operationResult, string $successMessageKey): Response
    {
        return response()->json([
            'isSuccess' => $operationResult->isSuccess,
            'message' => $operationResult->isSuccess ? localize($successMessageKey) : $operationResult->localizedError(),
        ]);
    }

    /** Chỉ redirect tới URL nội bộ để tránh Open Redirect (RedirectToLocalOrHome). */
    protected function redirectToLocalOrHome(?string $returnUrl): Response
    {
        $isLocalUrl = is_string($returnUrl) && str_starts_with($returnUrl, '/') && ! str_starts_with($returnUrl, '//') && ! str_starts_with($returnUrl, '/\\');

        return redirect($isLocalUrl ? $returnUrl : route('home'));
    }
}
