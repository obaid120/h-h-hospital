@props(['paginator'])
@if($paginator->hasPages())
    @php($start = max(1, $paginator->currentPage() - 1))
    @php($end = min($paginator->lastPage(), $paginator->currentPage() + 1))
    <nav {{ $attributes->class(['pagination']) }} aria-label="{{ __('ui.pagination.label') }}">
        <p class="text-xs text-muted">{{ __('ui.pagination.summary', ['from' => $paginator->firstItem() ?? 0, 'to' => $paginator->lastItem() ?? 0, 'total' => $paginator->total()]) }}</p>
        <div class="flex items-center gap-1">
            <x-ui.button variant="ghost" size="sm" :href="$paginator->previousPageUrl() ?: '#'" :disabled="$paginator->onFirstPage()" :aria-label="__('ui.actions.previous')"><x-icon name="chevron-left" class="size-4" /></x-ui.button>
            @if($start > 1)<a class="page-link" href="{{ $paginator->url(1) }}" aria-label="{{ __('ui.pagination.page', ['page' => 1]) }}">1</a>@if($start > 2)<span class="px-1 text-muted" aria-hidden="true">…</span>@endif @endif
            @foreach($paginator->getUrlRange($start, $end) as $page => $url)
                <a @class(['page-link', 'is-current' => $page === $paginator->currentPage()]) href="{{ $url }}" aria-label="{{ __('ui.pagination.page', ['page' => $page]) }}" @if($page === $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
            @endforeach
            @if($end < $paginator->lastPage())@if($end < $paginator->lastPage() - 1)<span class="px-1 text-muted" aria-hidden="true">…</span>@endif<a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="{{ __('ui.pagination.page', ['page' => $paginator->lastPage()]) }}">{{ $paginator->lastPage() }}</a>@endif
            <x-ui.button variant="ghost" size="sm" :href="$paginator->nextPageUrl() ?: '#'" :disabled="!$paginator->hasMorePages()" :aria-label="__('ui.actions.next')"><x-icon name="chevron-right" class="size-4" /></x-ui.button>
        </div>
    </nav>
@endif
