{{--
    Sticky announcement bar + a floating frosted-glass header bar.
    The header takes no space; `#main` and a leading hero offset themselves by `--nav-height`.
--}}
<div
    x-data="stickyHeader"
    @keydown.escape.window="closeMenu()"
    class="sticky top-(--sticky-offset) z-50"
>
    @if ($announcement)
        <div class="bg-ink px-4 py-2.5 text-center text-sm font-medium text-white [&_a]:text-white [&_a]:underline [&_strong]:tracking-[0.04em] [&_strong]:text-sun">
            {!! $announcement !!}
        </div>
    @endif

    <header x-ref="nav" class="gutter pointer-events-none absolute inset-x-0 top-full pt-3 md:pt-4">
        <div
            :class="scrolled ? 'shadow-[0_16px_40px_-16px_rgba(6,47,63,0.45)]' : 'shadow-[0_8px_24px_-16px_rgba(6,47,63,0.3)]'"
            class="shell pointer-events-auto flex items-center justify-between gap-4 rounded-full bg-white/60 py-1.5 pr-1.5 pl-3 text-ink ring-1 ring-white/70 backdrop-blur-2xl backdrop-saturate-150 transition-shadow md:py-2 md:pr-2 md:pl-4"
        >
            <a href="{{ home_url('/') }}" class="flex items-center gap-2.5 text-ink no-underline hover:text-ink">
                @if ($logo)
                    <img src="{{ esc_url($logo) }}" alt="{{ wp_strip_all_tags($siteName) }}" class="block h-8 w-auto max-w-[220px] md:h-9">
                @else
                    <span class="icon-tile size-8 rounded-[0.625rem] bg-brand text-white md:size-9 md:rounded-xl">
                        <x-icon-featherdash class="size-5 md:size-[1.375rem]" aria-hidden="true" />
                    </span>
                    <span class="font-display text-xl font-extrabold tracking-[-0.02em] md:text-2xl">{!! $siteName !!}</span>
                @endif
            </a>

            @if ($primaryNavigation->isNotEmpty())
                <nav aria-label="{{ __('Main', 'radicle') }}" class="hidden items-center gap-1 md:flex">
                    @foreach ($primaryNavigation->all() as $item)
                        <a
                            href="{{ $item->url }}"
                            @if ($item->target) target="{{ $item->target }}" @endif
                            @if ($item->active) aria-current="page" @endif
                            class="rounded-full px-4 py-2 text-[17px] font-medium text-ink no-underline transition-colors hover:text-brand aria-[current=page]:text-brand"
                        >{!! $item->label !!}</a>
                    @endforeach
                </nav>
            @endif

            <div class="flex items-center gap-2">
                @if ($headerCta)
                    <a
                        href="{{ $headerCta['url'] }}"
                        @if ($headerCta['target']) target="{{ $headerCta['target'] }}" rel="noopener" @endif
                        class="pill group min-h-12 bg-frost px-6 text-[17px] font-medium text-ink hover:bg-ice hover:text-ink max-sm:hidden"
                    >
                        {{ $headerCta['title'] }}
                        @include('partials.arrow')
                    </a>
                @endif

                <button
                    type="button"
                    @click="toggleMenu()"
                    :aria-expanded="open.toString()"
                    aria-controls="site-menu"
                    class="icon-tile size-10 cursor-pointer rounded-full border-0 bg-ink/5 text-ink md:hidden"
                >
                    <span class="sr-only">{{ __('Menu', 'radicle') }}</span>
                    <svg x-show="! open" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
                    <svg x-show="open" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
        </div>

        {{-- Mobile menu: a glass panel below the bar, out of flow so `--nav-height` stays put.
             Items animate in one by one (`.menu-stagger`, positions in `--i`) and leave in reverse. --}}
        @php($menuCount = count($primaryNavigation->all()) + ($headerCta ? 1 : 0))
        <div
            id="site-menu"
            x-show="panelVisible"
            x-cloak
            @click="if ($event.target.closest('a')) closeMenu()"
            class="gutter absolute inset-x-0 top-full md:hidden"
        >
            <div
                x-ref="menu"
                :data-open="open"
                style="--n: {{ $menuCount }}"
                class="menu-stagger shell pointer-events-auto mt-2 flex flex-col gap-1 rounded-3xl bg-white/70 p-2 text-ink shadow-[0_16px_40px_-16px_rgba(6,47,63,0.45)] ring-1 ring-white/70 backdrop-blur-2xl backdrop-saturate-150"
            >
                @foreach ($primaryNavigation->all() as $item)
                    <a
                        href="{{ $item->url }}"
                        style="--i: {{ $loop->index }}"
                        @if ($item->target) target="{{ $item->target }}" @endif
                        @if ($item->active) aria-current="page" @endif
                        class="rounded-2xl px-4 py-3 text-[17px] font-medium text-ink no-underline hover:bg-ink/5 hover:text-ink aria-[current=page]:bg-ink/5 aria-[current=page]:text-brand"
                    >{!! $item->label !!}</a>
                @endforeach

                @if ($headerCta)
                    <a
                        href="{{ $headerCta['url'] }}"
                        style="--i: {{ $menuCount - 1 }}"
                        @if ($headerCta['target']) target="{{ $headerCta['target'] }}" rel="noopener" @endif
                        class="pill group mt-1 min-h-12 bg-frost px-6 text-[17px] font-medium text-ink hover:bg-ice hover:text-ink sm:hidden"
                    >
                        {{ $headerCta['title'] }}
                        @include('partials.arrow')
                    </a>
                @endif
            </div>
        </div>
    </header>
</div>
