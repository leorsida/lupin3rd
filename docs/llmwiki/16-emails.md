---
id: emails
title: Emails
status: authored
updated: 2026-09-16
sources: [`inc/class-mail.php`]
---

# Emails

The theme sends three messages. All of them are plain text, all of them go
through `Lupin3rd\Mail::send()`, and each one can be rewritten from a plugin
without touching the theme.

## The three

| Key | Filter | Sent when | To |
|---|---|---|---|
| `dispatch_confirmation` | `lupin3rd_mail_dispatch_confirmation` | somebody asks to join the Thursday dispatch | the address that asked |
| `comment_reported` | `lupin3rd_mail_comment_reported` | a contribution is reported, **first report only** | the desk (`admin_email`) |
| `new_contribution` | `lupin3rd_mail_new_contribution` | an approved contribution appears under a piece | everybody who already wrote under it |

## Changing the wording

There are two ways, and the right one depends on whether the change is a
translation or a decision.

**A different wording in the same language — the translation file.** Every
string is `__( …, 'lupin3rd' )`, so `languages/lupin3rd-it_IT.po` is where the
desk's own phrasing belongs. This survives theme updates, is how the English
build will get its own version, and needs no code at all. It does need a `.po`
editor and a deploy.

**Something the code cannot know — the filter.** A different sender name per
message, a line that only makes sense on this installation, a subject built
from something the theme never sees.

```php
add_filter(
    'lupin3rd_mail_new_contribution',
    static function ( array $message, array $context ): array {
        $message['subject'] = sprintf(
            'Si discute ancora di «%s»',
            get_the_title( $context['post'] )
        );

        return $message;
    },
    10,
    2
);
```

`$message` carries `to`, `subject`, `body` and `headers`. `$context` carries
the objects the message is about: `post`, `comment`, `user` and `unfollow` for
`new_contribution`; `subscriber` and `link` for `dispatch_confirmation`;
`comment` and `link` for `comment_reported`.

A filter that returns the wrong shape is ignored rather than obeyed, one field
at a time: an empty subject or a missing address falls back to the default.
Sending a broken message because a plugin returned `null` would be worse than
sending the one the theme wrote.

## Two things not to change

**The unsubscribe link in `new_contribution`.** It is in the body and in a
`List-Unsubscribe` header, and it is the difference between a notification and
a nuisance. A filter can move it or reword the line around it; a filter that
drops it turns this domain into one that mail providers learn to file away.

**Plain text.** The colophon promises «nessun tracciamento nelle email: niente
pixel, niente link riscritti». Plain text is what makes that true by
construction instead of by vigilance: a tracking pixel needs an `<img>`, and in
a text body there is nowhere to put one. Sending HTML is possible — add a
`Content-Type` header from the filter — but from that moment the promise is
something somebody has to keep watching, and it is written on a page the
readers of this site actually read.

## What is not here

Password recovery, new-account notices and anything else on `wp-login.php` are
WordPress's own messages, not the theme's. They have their own filters
(`retrieve_password_message`, `wp_new_user_notification_email`) and none of them
passes through `Mail::send()`.
