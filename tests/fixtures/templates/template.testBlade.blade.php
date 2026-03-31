<div class="test-container">
    <h1>Hello {{ $name }}</h1>
    <ul>
        @foreach($items as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>
</div>
