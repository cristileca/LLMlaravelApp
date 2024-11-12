<div>
    <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Comentarii</h2>
        <hr class="my-2 border-gray-300 dark:border-gray-600">
    </div>

    <ul>
        @foreach ($comments as $comment)
            <li class="mb-4 p-4 rounded-xl bg-gray-100 dark:bg-gray-900">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-white">
                            {{ $comment->user->name }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            {{ $comment->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @if ($editingCommentId === $comment->id)
                        <div>
                            <button wire:click="updateComment"
                                class="text-gray-800 dark:text-white bg-gray-200 dark:bg-gray-800 px-4 py-2 rounded-md ml-2 cursor-pointer">
                                Confirmă
                            </button>
                            <button wire:click="cancelEdit"
                                class="text-gray-800 dark:text-white bg-gray-200 dark:bg-gray-800 px-4 py-2 rounded-md ml-2 cursor-pointer">
                                Anulează
                            </button>
                        </div>
                    @else
                        <button wire:click="editComment({{ $comment->id }}, '{{ addslashes($comment->text) }}')"
                            class="text-gray-800 dark:text-white bg-gray-200 dark:bg-gray-800 px-4 py-2 rounded-md ml-2 cursor-pointer">
                            Editează
                        </button>
                    @endif
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-gray-800 dark:text-gray-200 flex-grow mr-4">
                        @if ($editingCommentId === $comment->id)
                            <textarea wire:model="editingCommentText"
                                class="bg-gray-100 dark:bg-gray-900 w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea>
                            @error('editingCommentText')
                                <span class="text-red-500 mt-2 block">{{ $message }}</span>
                            @enderror
                        @else
                            {{ $comment->text }}
                        @endif
                    </p>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-4">
        {{ $comments->links() }}
    </div>

    <div class="mt-6">
        <textarea id="commentTextArea" wire:model="commentText"
            class="bg-gray-100 dark:bg-gray-900 w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
            placeholder="Adauga comentariu..."></textarea>
        @error('commentText')
            <span class="text-red-500 mt-2 block">{{ $message }}</span>
        @enderror

        <button wire:click="addComment"
            class="text-gray-800 dark:text-white bg-gray-200 dark:bg-gray-800 px-4 py-2 rounded-md mt-4 cursor-pointer">
            Post Comment
        </button>
    </div>
</div>
