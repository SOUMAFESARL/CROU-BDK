@props([
    'title' => 'Application Sales',
    'headers' => ['Application', 'Sales', 'Change', 'Avg Price', 'Total'],
    'viewAllLink' => '#',
    'viewAllText' => 'View all Projects',
    'hasCheckbox' => true,
    'cardOptions' => true,
    'tableClass' => 'table-hover table-borderless'
])

<div class="card table-card">
    <div class="card-header">
        <h5>{{ $title }}</h5>
        
        @if($cardOptions)
        <div class="card-header-right">
            <ul class="list-unstyled card-option">
                <li><i class="feather icon-maximize full-card" title="Maximize"></i></li>
                <li><i class="feather icon-minus minimize-card" title="Minimize"></i></li>
                <li><i class="feather icon-trash-2 close-card" title="Close"></i></li>
            </ul>
        </div>
        @endif
    </div>
    
    <div class="card-block">
        <div class="table-responsive">
            <table class="table {{ $tableClass }}">
                <thead>
                    <tr>
                        @if($hasCheckbox && count($headers) > 0)
                        <th>
                            <div class="chk-option">
                                <div class="checkbox-fade fade-in-primary">
                                    <label class="check-task">
                                        <input type="checkbox" value="" class="select-all">
                                        <span class="cr">
                                            <i class="cr-icon feather icon-check txt-default"></i>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            {{ $headers[0] }}
                        </th>
                        @foreach(array_slice($headers, 1) as $header)
                        <th>{{ $header }}</th>
                        @endforeach
                        @else
                        @foreach($headers as $header)
                        <th>{{ $header }}</th>
                        @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody>
                    {{ $slot }}
                </tbody>
            </table>
            
            @if($viewAllLink)
            <div class="text-center mt-3">
                <a href="{{ $viewAllLink }}" class="b-b-primary text-primary">
                    {{ $viewAllText }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>