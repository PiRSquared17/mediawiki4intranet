UPDATE archive, `user`          SET ar_user_text='NEW' WHERE ar_user=user_id AND user_name='OLD';
UPDATE filearchive, `user`      SET fa_user_text='NEW' WHERE fa_user=user_id AND user_name='OLD';
UPDATE image, `user`            SET img_user_text='NEW' WHERE img_user=user_id AND user_name='OLD';
UPDATE oldimage, `user`         SET oi_user_text='NEW' WHERE oi_user=user_id AND user_name='OLD';
UPDATE recentchanges, `user`    SET rc_user_text='NEW' WHERE rc_user=user_id AND user_name='OLD';
UPDATE revision, `user`         SET rev_user_text='NEW' WHERE rev_user=user_id AND user_name='OLD';
UPDATE wikilog_comments, `user` SET wlc_user_text='NEW' WHERE wlc_user=user_id AND user_name='OLD';
UPDATE `user`                   SET user_name='NEW' WHERE user_name='OLD';
