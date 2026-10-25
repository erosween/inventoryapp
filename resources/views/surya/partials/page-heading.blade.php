<div class="surya-page-heading">
    <div class="surya-page-heading-copy">
        <h1>{{ $title }}</h1>
        @if(!empty($description))<p>{{ $description }}</p>@endif
    </div>
    @if(isset($actions))<div class="surya-page-heading-actions">{{ $actions }}</div>@endif
</div>
