# CoremailReplyPreprocess

![](https://github.com/nielspeen/CoremailReplyPreprocess/blob/main/Public/coremail.png?raw=true){height=100}

FreeScout does not always parse Coremail replies correctly, making it seem 
like the customer replied with an empty e-mail. 

This module hooks into the fetch_emails.separate_reply.preprocess_body filter
to preprocess e-mails from affected domains.

Affected e-mail domains: 163.com, 126.com and yeah.net.
