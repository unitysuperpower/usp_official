{{-- Contact Reply Email Template --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .content { margin-bottom: 20px; }
        .original-message { background-color: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        .reply { background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
        .footer { font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Reply to Your Contact Message</h2>
            <p>Thank you for contacting us. Here's our response to your message.</p>
        </div>

        <div class="content">
            <h3>Your Original Message:</h3>
            <div class="original-message">
                <p><strong>Subject:</strong> {{ $originalMessage->subject }}</p>
                <p><strong>Message:</strong></p>
                <p>{{ $originalMessage->message }}</p>
                <p><strong>Sent on:</strong> {{ $originalMessage->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>

            <h3>Our Reply:</h3>
            <div class="reply">
                {!! nl2br(e($replyContent)) !!}
            </div>
        </div>

        <div class="footer">
            <p>This is an automated response. Please do not reply to this email.</p>
            <p>If you have further questions, please contact us through our website.</p>
        </div>
    </div>
</body>
</html>