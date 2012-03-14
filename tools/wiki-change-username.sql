SET NAMES utf8;
SET @oldname = '';
SET @newname = '';
UPDATE `archive`, `user`          SET ar_user_text=@newname WHERE ar_user=user_id AND user_name=@oldname;
UPDATE `filearchive`, `user`      SET fa_user_text=@newname WHERE fa_user=user_id AND user_name=@oldname;
UPDATE `image`, `user`            SET img_user_text=@newname WHERE img_user=user_id AND user_name=@oldname;
UPDATE `page`                     SET page_title=REPLACE(@newname,' ','_') WHERE page_namespace IN (2, 3) AND page_title=REPLACE(@oldname,' ','_');
UPDATE `oldimage`, `user`         SET oi_user_text=@newname WHERE oi_user=user_id AND user_name=@oldname;
UPDATE `recentchanges`, `user`    SET rc_user_text=@newname WHERE rc_user=user_id AND user_name=@oldname;
UPDATE `revision`, `user`         SET rev_user_text=@newname WHERE rev_user=user_id AND user_name=@oldname;
UPDATE `wikilog_comments`, `user` SET wlc_user_text=@newname WHERE wlc_user=user_id AND user_name=@oldname;
UPDATE `wikilog_authors`          SET wla_author_text=@newname WHERE wla_author_text=@oldname;
UPDATE `wikilog_posts`            SET wlp_authors=REPLACE(wlp_authors,@oldname,@newname);
UPDATE `user`                     SET user_name=@newname WHERE user_name=@oldname;
UPDATE `page`, `revision`, `text`
    SET old_text=REPLACE(REPLACE(old_text,@oldname,@newname),REPLACE(@oldname,' ','_'),REPLACE(@newname,' ','_'))
    WHERE old_text LIKE CONCAT('%',REPLACE(@oldname,' ','_'),'%') AND old_id=rev_text_id AND rev_id=page_latest;
DELETE FROM `objectcache`;
