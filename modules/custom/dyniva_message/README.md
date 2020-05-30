使用说明
===============

基于message module实现用户订阅功能，支持message_notify的通知插件，并提供通知信息页面以便查询过往通知。

使用前需要先进行以下设置：

到 /admin/structure/message 自定义信息类型，创建message template

到 /admin/config/system/dyniva_message_settings 激活将要实现订阅的信息类型，否则将不会产生任何效果。

到 /config/message/message-subscribe 激活的通知插件，例如email和SMS (需要启用sms模块才能支持)，安装更多message_notify的模块会出现更多。注意不要修改Flag prefix。

message entity被创建时会触发通知机制，存在两种通知机制：订阅通知与个人通知。全局通知会通知订阅用户，用户订阅的消息类型可在user edit page进行设置。个人通知需要对message配置receiver字段并保证字段有值，消息会通知receiver所指向的用户。

短信通知：

需要启用sms、 sms_user模块，然后到Phone number settings进行设置，把Phone number field加到entity type:user上，用户就可以添加phone number，有且仅当存在phone number时才会发出sms消息。进行sms调试时需要注意，sms默认有Drupal log方式用于调试，所以设置phone number的验证码可以在日志中获取，但是由于Drupal log默认启用队列，没有run cron时不会发出信息，所以建议先选中skip queue把队列关掉，便可立即收到sms消息。