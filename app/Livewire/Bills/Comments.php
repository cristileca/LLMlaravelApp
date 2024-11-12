<?php

namespace App\Livewire\Bills;

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Comments extends Component
{
    use WithPagination;
    public $bill;
    public $commentText = '';
    public $editingCommentText = '';
    public $editingCommentId = null;
    protected $rules = [
        'commentText' => 'required|string|max:1000',
        'editingCommentText' => 'required|string|max:1000',
    ];
    public function mount($bill)
    {
        $this->bill = $bill;
    }
    public function addComment()
    {
        $this->validate([
            'commentText' => 'required|string|max:1000',
        ]);
        Comment::create([
            'text' => trim($this->commentText),
            'user_id' => Auth::id(),
            'bill_id' => $this->bill->id,
        ]);
        $this->commentText = '';
        $this->resetPage();
    }
    public function editComment($commentId, $commentText)
    {
        $comment = Comment::find($commentId);
        if ($comment && $comment->user_id == Auth::id()) {
            $this->editingCommentId = $commentId;
            $this->editingCommentText = $commentText;
        }
    }
    public function updateComment()
    {
        $this->validate([
            'editingCommentText' => 'required|string|max:1000',
        ]);
        $comment = Comment::find($this->editingCommentId);
        if ($comment && $comment->user_id == Auth::id()) {
            $comment->text = trim($this->editingCommentText);
            $comment->save();
        }
        $this->resetEditState();
    }
    public function cancelEdit()
    {
        $this->resetEditState();
    }
    private function resetEditState()
    {
        $this->editingCommentId = null;
        $this->editingCommentText = '';
    }
    public function render()
    {
        $currentUser = Auth::user();
        $query = Comment::with('user')
            ->whereBillId($this->bill->id);
        if (!($currentUser->hasRole("super_admin"))) {
            $query->where('user_id', $currentUser->id);
        }
        return view('livewire.bills.comments', [
            'comments' => $query->latest()->paginate(5),
            'userId' => $currentUser->id
        ]);
    }
}
