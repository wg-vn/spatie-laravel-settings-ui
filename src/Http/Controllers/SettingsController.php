<?php

namespace WgVn\SettingsUi\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use WgVn\SettingsUi\SettingsPage;
use WgVn\SettingsUi\Support\PageRegistry;

class SettingsController extends Controller
{
    public function __construct(protected PageRegistry $pages) {}

    /**
     * Opens the first page the user may see, or explains why there is none.
     */
    public function index(): View|RedirectResponse
    {
        $first = $this->pages->accessible()->first();

        if ($first !== null) {
            return redirect()->route('settings-ui.show', $first->getSlug());
        }

        return view('settings-ui::pages.empty', [
            'navigation' => collect(),
        ]);
    }

    public function show(string $page): View
    {
        $page = $this->page($page);

        return view('settings-ui::pages.edit', [
            'page' => $page,
            'fields' => $page->getFields(),
            'data' => $page->getFormData(),
            'canEdit' => $page->canEdit(),
            'navigation' => $this->pages->accessible(),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $page = $this->page($page);

        $page->save($request);

        return redirect($page->getRedirectUrl() ?? route('settings-ui.show', $page->getSlug()))
            ->with('settings-ui.saved', $page->getSavedNotificationTitle());
    }

    protected function page(string $slug): SettingsPage
    {
        $page = $this->pages->find($slug);

        abort_if($page === null, 404);
        abort_unless($page->canAccess(), 403);

        return $page;
    }
}
