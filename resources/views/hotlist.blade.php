@extends('layouts.layout')

@section('main_content')
    <section class="container">
        <div class="row">
            <div class="col-sm-2">
                <select class="selectpicker pull-right" title="请选择站点" name="site" data-width="fit" id="site-select">
                    @foreach(\App\Service\Novel\NovelSiteFactory::$novelSites as $site => $val)
                        @if ($val['hotlist'])
                            <option value="{{$site}}">{{$val['name']}}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-sm-10">
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th>类型</th>
                        <th>书名</th>
                        <th>作者</th>
                        <th style="width: 40%">最新章节</th>
                        <th>-</th>
                    </tr>
                    </thead>
                    <tbody id="novel-table"></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        // 更新列表
        function updateTable(site, novels) {
            $('#novel-table').empty();
            for (let i = 0; i < novels.length; i++) {
                $('#novel-table').append(`
                    <tr>
                        <td>「${novels[i]['category'] || '----'}」</td>
                        <td>
                            <a href="${novels[i]['href']}" target="_blank">${novels[i]['title']}</a>
                        </td>
                        <td>${novels[i]['author'] || '----'}</td>
                        <td>${novels[i]['latest_chapter_name'] || '----'}</td>
                        <td>
                        ${novels[i]['is_collect'] === 1 ? `
                        <button type="button" class="btn btn-sm" disabled>已收藏</button>
                        ` : `
                        <button type="button" class="btn btn-sm btn-success collectNovel" data-uri="${novels[i]['uri']}" data-site="${site}">
                            <span class="glyphicon glyphicon-plus"></span>收藏
                        </button>
                        `}
                        </td>
                    </tr>`);
            }
        }

        // 缓存搜索结果
        function cacheSearchResults(site, results) {
            let value = {
                'site': site,
                'results': results
            }
            localStorage.setItem('hotlist-cache', JSON.stringify(value));
        }

        // 切换站点
        $(document).on('change', '#site-select', function () {
            let site = $(this).val();
            $.ajax({
                url: '/novel/hotlist',
                type: 'get',
                cache: false,
                dataType: 'json',
                data: {
                    'site': site
                },
                success: function (data) {
                    if (data.code == 0) {
                        updateTable(site, data.data.list);
                        cacheSearchResults(site, data.data.list);
                    } else {
                        bootbox.alert('搜索失败: ' + data.message);
                    }
                },
                error: function (e) {
                    bootbox.alert('搜索失败, error: ' + e.responseText);
                }
            });
        });

        // 收藏
        $(document).on('click', '.collectNovel', function () {
            let dataUri = $(this).data('uri');
            let dataSite = $(this).data('site');
            let button = $(this);
            button.attr('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin fa-fw"></i>收藏');
            $.ajax({
                url: '/novel/collect',
                type: 'post',
                cache: false,
                dataType: 'json',
                data: {
                    'uri': dataUri,
                    'site': dataSite
                },
                success: function (data) {
                    if (data.code == 0) {
                        button.removeClass('btn-success');
                        button.html('已收藏');
                    } else {
                        bootbox.alert('收藏失败: ' + data.message);
                        button.attr('disabled', false);
                        button.html(`<span class="glyphicon glyphicon-plus"></span>收藏`);
                    }
                },
                error: function (e) {
                    bootbox.alert(e.responseText);
                    button.attr('disabled', false);
                    button.html(`<span class="glyphicon glyphicon-plus"></span>收藏`);
                }
            });
        });

        $(document).ready(function () {
            let value = localStorage.getItem('hotlist-cache');
            if (value.length > 0) {
                let data = JSON.parse(value);
                $('#site-select').val(data.site);
                updateTable(data.site, data.results);
            }
        });
    </script>
@endsection
