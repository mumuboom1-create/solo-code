-- ----------------------------
-- Sqlite数据库升级脚本
-- 适用于PbootCMS 3.1.5
-- ----------------------------

--
-- 新增索引以优化大数据网站加载速度
--

CREATE UNIQUE INDEX ay_content_unique on ay_content (`sorting`, `istop`, `isrecommend`, `isheadline`, `date`, `id`);
--
-- 
--
