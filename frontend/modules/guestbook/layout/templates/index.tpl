{*
	variables that are available:
	- {$frm}: contains the post form
	- {$comments}: contains the comments that are published
*}
<div id="guestbook">
	<section id="guestbookCommentForm" class="mod">
		<div class="inner">
			<header class="hd">
				<h3 id="{$actComment}">{$msgComment|ucfirst}</h3>
			</header>
			<div class="bd">
				{option:commentIsInModeration}<div class="message warning"><p>{$msgBlogCommentInModeration}</p></div>{/option:commentIsInModeration}
				{option:commentIsSpam}<div class="message error"><p>{$msgBlogCommentIsSpam}</p></div>{/option:commentIsSpam}
				{option:commentIsAdded}<div class="message success"><p>{$msgBlogCommentIsAdded}</p></div>{/option:commentIsAdded}
				{form:guestbook_comment}
					<div class="alignBlocks">
						<p {option:txtAuthorError}class="errorArea"{/option:txtAuthorError}>
							<label for="author">{$lblName|ucfirst}<abbr title="{$lblRequiredField}">*</abbr></label>
							{$txtAuthor} {$txtAuthorError}
						</p>
						<p {option:txtEmailError}class="errorArea"{/option:txtEmailError}>
							<label for="email">{$lblEmail|ucfirst}<abbr title="{$lblRequiredField}">*</abbr></label>
							{$txtEmail} {$txtEmailError}
						</p>
					</div>
					<p class="bigInput{option:txtWebsiteError} errorArea{/option:txtWebsiteError}">
						<label for="website">{$lblWebsite|ucfirst}</label>
						{$txtWebsite} {$txtWebsiteError}
					</p>
					<p class="bigInput{option:txtMessageError} errorArea{/option:txtMessageError}">
						<label for="message">{$lblMessage|ucfirst}<abbr title="{$lblRequiredField}">*</abbr></label>
						{$txtMessage} {$txtMessageError}
					</p>
					<p>
						<input class="inputSubmit" type="submit" name="comment" value="{$msgComment|ucfirst}" />
					</p>
				{/form:guestbook_comment}
			</div>
			<div class="bd content">
				{iteration:comments}
					{* Do not alter the id! It is used as an anchor *}
					<div id="comment-{$comments.id}" class="comment">
						<div class="imageHolder">
							{option:comments.website}<a href="{$comments.website}">{/option:comments.website}
								<img src="{$FRONTEND_CORE_URL}/layout/images/default_author_avatar.gif" width="48" height="48" alt="{$comments.author}" class="replaceWithGravatar" data-gravatar-id="{$comments.gravatar_id}" />
							{option:comments.website}</a>{/option:comments.website}
						</div>
						<div class="commentContent">
							<p class="commentAuthor">
								{option:comments.website}<a href="{$comments.website}">{/option:comments.website}{$comments.author}{option:comments.website}</a>{/option:comments.website}
								{$lblWrote}
								{$comments.created_on|timeago}
							</p>
							<div class="commentText content">
								{$comments.text|cleanupplaintext}
							</div>
						</div>
					</div>
				{/iteration:comments}
			</div>
		</div>
	{include:core/layout/templates/pagination.tpl}
	</section>
</div>