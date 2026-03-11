<div>
    @if($showChat)
    <!-- Chat Box -->
    <div class="fixed bottom-24 right-6 z-40 w-96 h-[32rem] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white p-4 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold">Support Team</h3>
                    <p class="text-xs text-indigo-100">We're here to help!</p>
                </div>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50" id="chatMessages" wire:poll.5s="loadMessages">
            @forelse($messages as $msg)
                <div class="flex {{ $msg->is_admin ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[70%]">
                        <div class="rounded-2xl px-4 py-2 {{ $msg->is_admin ? 'bg-white text-gray-800 shadow' : 'bg-indigo-600 text-white' }}">
                            <p class="text-sm">{{ $msg->message }}</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 {{ $msg->is_admin ? 'text-left' : 'text-right' }}">
                            {{ $msg->created_at->format('g:i A') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 mt-8">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="text-sm">No messages yet</p>
                    <p class="text-xs mt-1">Start a conversation with us!</p>
                </div>
            @endforelse
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-gray-200">
            <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                <input 
                    type="text" 
                    wire:model="message" 
                    placeholder="Type your message..." 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                    autocomplete="off"
                >
                <button 
                    type="submit" 
                    class="bg-indigo-600 text-white p-2 rounded-full hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    :disabled="!message.trim()"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('messageSent', () => {
                setTimeout(() => {
                    const chatMessages = document.getElementById('chatMessages');
                    if (chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }, 100);
            });

            Livewire.on('chatToggled', () => {
                setTimeout(() => {
                    const chatMessages = document.getElementById('chatMessages');
                    if (chatMessages) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                }, 100);
            });
        });
    </script>
    @endif
</div>
