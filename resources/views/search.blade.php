@extends('layouts.layout')

@section('main_content')
    <section class="container">
        <form class="form-inline" onsubmit="return submitForm()">
            <div class="form-group">
                <select class="selectpicker" name="site" data-width="fit">
                    <option value="xbqg">新笔趣阁</option>
                    <option value="biquku">笔趣库</option>
                    <option value="bxwx">笔下文学</option>
                </select>
                <div class="input-group">
                    <input type="text" name="keyword" class="form-control" maxlength="20">
                    <div class="input-group-addon">
                        <span id="tosearch" class="glyphicon glyphicon-search" aria-hidden="true"></span>
                        <div id="searching" hidden><i class="fa fa-spinner fa-spin fa-fw"></i>搜索中...</div>
                    </div>
                </div>
            </div>
        </form>
        <hr>

        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>类型</th>
                <th>书名</th>
                <th>作者</th>
                <th style="width: 35%">简介</th>
                <th style="width: 20%">最新章节</th>
                <th>-</th>
            </tr>
            </thead>
            <tbody id="novel-table"></tbody>
        </table>
    </section>
@endsection

@section('js')
    <script>
        // 更新列表
        function updateTable(site, novels) {
            for (let i = 0; i < novels.length; i++) {
                $('#novel-table').append(`
                <tr>
                    <td>「${novels[i]['category'] || '----'}」</td>
                    <td><a href="${novels[i]['href']}" target="_blank">${novels[i]['title']}</a></td>
                    <td>${novels[i]['author']}</td>
                    <td><small>${novels[i]['intro'] || '----'}</small></td>
                    <td><a href="${novels[i]['latest_chapter_url']}" target="_blank">${novels[i]['latest_chapter_name']}</a></td>
                    <td>
                    ${novels[i]['is_collect'] === 1 ? `
                    <button type="button" class="btn btn-sm" disabled>已收藏</button>
                    ` : `
                    <button type="button" class="btn btn-sm btn-success collectNovel" data-uri="${novels[i]['uri']}" data-site="${site}">
                        <span class="glyphicon glyphicon-plus"></span>收藏
                    </button>
                    `}
                    </td>
                </tr>
                `);
            }
        }

        // 缓存搜索结果
        function cacheSearchResults(site, keyword, results) {
            let value = {
                'site': site,
                'keyword': keyword,
                'results': results
            }
            localStorage.setItem('search-cache', JSON.stringify(value));
        }

        // 切换搜索栏状态
        function changeSearchStatus(onOff) {
            if (onOff) {
                $('input[name="keyword"]').attr('readonly', 'readonly');
                $('#tosearch').hide();
                $('#searching').show();
            } else {
                $('input[name="keyword"]').attr('readonly', false);
                $('#searching').hide();
                $('#tosearch').show();
            }
        }

        // 搜索
        function submitForm() {
            $('#novel-table').empty();
            let keyword = $('input[name="keyword"]').val();
            let site = $('select[name="site"]').val();
            changeSearchStatus(true);
            $.ajax({
                url: '/novel/search',
                type: 'get',
                cache: false,
                dataType: 'json',
                data: {
                    'keyword': keyword,
                    'site': site
                },
                success: function (data) {
                    if (data.code == 0) {
                        updateTable(data.data.site, data.data.list);
                        cacheSearchResults(site, keyword, data.data.list);
                    } else {
                        bootbox.alert('搜索失败: ' + data.message);
                    }
                    changeSearchStatus(false);
                },
                error: function (e) {
                    bootbox.alert('搜索失败, error: ' + e.responseText);
                    changeSearchStatus(false);
                }
            });
            return false;
        }

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
                    bootbox.alert('收藏失败: ' + e.responseText);
                    button.attr('disabled', false);
                    button.html(`<span class="glyphicon glyphicon-plus"></span>收藏`);
                }
            });
        });

        $(document).ready(function () {
            let value = localStorage.getItem('search-cache');
            if (value.length > 0) {
                let data = JSON.parse(value);
                $('input[name="keyword"]').val(data.keyword);
                $('select[name="site"]').val(data.site);
                updateTable(data.site, data.results);
            }
        });
    </script>
@endsection
