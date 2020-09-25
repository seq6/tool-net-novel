@extends('layouts.layout')

@section('main_content')
    <section class="container">
        <ul class="media-list" id="novel-list"></ul>
    </section>
@endsection

@section('js')
    <script>
        // 更新小说库
        function updateNovelLibrary(novels) {
            $('#novel-list').empty();
            for (let i = 0; i < novels.length; i++) {
                let processSync = parseInt(novels[i]['done_chapter'] / novels[i]['chapter'] * 100);
                $('#novel-list').append(`
                    <li class="media">
                        <div class="media-left">
                            <img class="media-object" height="250px" src="${novels[i]['cover']}">
                        </div>
                        <div class="media-body">
                            <h3 class="media-heading">${novels[i]['title']}</h3>
                            <p>作者: ${novels[i]['author']}</p>
                            <p>类型: ${novels[i]['category']}</p>
                            <p>总章节: ${novels[i]['chapter']}</p>
                            <p>
                                <small>${novels[i]['intro']}</small>
                            </p>
                            ${novels[i]['chapter'] == novels[i]['done_chapter'] ? `
                            <div class="btn-group" role="group">
                                <a href="/novel/download/zip?novel_id=${novels[i]['id']}" class="btn btn-primary">下载zip</a>
                                <a href="/novel/download/txt?novel_id=${novels[i]['id']}" class="btn btn-default">下载txt</a>
                            </div>
                            ` : `
                            同步中: ${novels[i]['done_chapter']} / ${novels[i]['chapter']}
                            <div class="progress">
                                <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar"
                                style="width: ${processSync}%"> </div>
                            </div>
                            `}
                        </div>
                        <div class="media-right">
                            <button type="button" class="btn btn-danger delNovel" data-id="${novels[i]['id']}" data-title="${novels[i]['title']}">删除</button>
                        </div>
                    </li>`);
            }
        }

        $(document).ready(function () {
            $.ajax({
                url: '/novel/library',
                type: 'get',
                cache: false,
                dataType: 'json',
                data: {},
                success: function (data) {
                    if (data.code == 0) {
                        updateNovelLibrary(data.data.list);
                    } else {
                        bootbox.alert('获取书库失败: ' + data.message);
                    }
                },
                error: function (e) {
                    bootbox.alert('获取书库失败, error: ' + e.responseText);
                }
            });
        });

        $(document).on('click', '.delNovel', function () {
            let mediaItem = $(this).parent().parent();
            let title = $(this).data('title');
            let id = $(this).data('id');
            bootbox.confirm({
                message: `是否确认删除<strong> ${title} </strong> ？`,
                buttons: {
                    confirm: {
                        label: '<i class="fa fa-check"></i> 是，删除',
                        className: 'btn-danger'
                    },
                    cancel: {
                        label: '<i class="fa fa-times"></i> 保留小说',
                        className: 'btn-default'
                    }
                },
                callback: function (result) {
                    if (result) {
                        $.ajax({
                            url: '/novel/delete',
                            type: 'post',
                            cache: false,
                            dataType: 'json',
                            data: {
                                'novel_id': id
                            },
                            success: function (data) {
                                if (data.code == 0) {
                                    mediaItem.remove();
                                } else {
                                    bootbox.alert({
                                        message: '删除失败: ' + data.message,
                                        callback: function () {
                                            window.location.reload();
                                        }
                                    });
                                }
                            },
                            error: function (e) {
                                bootbox.alert('删除失败, error: ' + e.responseText);
                            }
                        });
                    }
                }
            });
        });
    </script>
@endsection
