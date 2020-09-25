<header>
    <nav class="navbar navbar-default">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                        data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">
                    <span class="glyphicon glyphicon-book" aria-hidden="true"></span>
                </a>
            </div>

            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li @if (request()->path() == 'search') class="active" @endif><a href="/search">搜索</a></li>
                    <li @if (request()->path() == 'library') class="active" @endif><a href="/library">书架</a></li>
                    <li @if (request()->path() == 'hotlist') class="active" @endif><a href="/hotlist">热门</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
