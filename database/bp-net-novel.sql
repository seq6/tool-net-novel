/**
  小说信息表
 */
CREATE TABLE novel
(
    id         integer
        constraint novel_pk primary key autoincrement,                -- 自增id
    title      text     default '' not null,                          -- 书名
    site       text     default '' not null,                          -- 网站
    category   text     default '' not null,                          -- 类型
    author     text     default '' not null,                          -- 作者
    cover      text     default '' not null,                          -- 封面图
    intro      text     default '' not null,                          -- 简介
    status     int      default 1 not null check ( status in (1, 2)), -- 状态: 1-连载中, 2-已完结
    chapter    int      default 0 not null check ( chapter >= 0 ),    -- 章节总数
    uri        text     default '' not null,                          -- 网页uri
    created_at datetime default current_timestamp not null,           -- 创建时间
    updated_at datetime default current_timestamp not null,           -- 更新时间
    constraint uniq_uri_site unique (uri, site)
);

/**
  章节信息表
 */
CREATE TABLE chapter
(
    id         integer
        constraint chapter_pk primary key autoincrement,           -- 自增id
    novel_id   int      default 0 not null check ( novel_id > 0 ), -- 书id
    seq        int      default 0 not null check ( seq > 0 ),      -- 章节编号
    title      text     default '' not null,                       -- 标题
    uri        text     default '' not null,                       -- 网页uri
    sync_at    int      default 0 not null check ( sync_at >= 0 ), -- 最新同步时间戳
    created_at datetime default current_timestamp not null,        -- 创建时间
    updated_at datetime default current_timestamp not null,        -- 更新时间
    constraint uniq_nid_seq unique (novel_id, seq)
);
