<ul class="dash-submenu">
    @foreach ($childs as $child)
        @php
                $staus = true;
                if(!empty($child->dependency))
                {
                    $dependency = explode(',',$child->dependency);
                    $staus = false;
                    if(!empty($active_module))
                    {
                        if(!empty(array_intersect($dependency,$active_module)))
                        {
                            $staus = true;
                        }
                    }
                }
                if(!empty($child->disable_module))
                {
                    $disable_module = explode(',',$child->disable_module);
                    $staus = false;
                    if(!empty($active_module))
                    {
                        if(count(array_intersect($disable_module, $active_module)) != count($disable_module))
                        {
                            $staus = true;
                        }
                    }
                }
        @endphp
        @if ($staus == true)
            @can($child->permissions)
                <li class="dash-item">
                    <a class="dash-link" href="{{ empty($child->route)?'#!':route($child->route) }}">{{ __($child->title) }}
                    @if(count($child->childs))
                    <span class="dash-arrow">
                        <i data-feather="chevron-right"></i>
                    </span>
                    @endif
                    </a>
                    @include('partials.submenu',['childs'=>$child->childs])
                </li>
            @endcan
        @endif
    @endforeach
</ul>
