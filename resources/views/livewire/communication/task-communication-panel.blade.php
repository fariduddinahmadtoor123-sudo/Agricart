<div class="agricart-task-comms">
    <header class="agricart-task-comms__head">
        <h3>{{ __('communication.tasks.heading') }}</h3>
        <p>{{ __('communication.tasks.lead') }}</p>
    </header>

    @if ($openTask)
        <section class="agricart-task-comms__thread" wire:key="task-thread-{{ $openTask->id }}">
            <header class="agricart-task-comms__thread-head">
                <div>
                    <p class="agricart-task-comms__status is-{{ $openTask->status }}">
                        {{ __('communication.tasks.statuses.'.$openTask->status) }}
                        · {{ __('communication.tasks.priorities.'.$openTask->priority) }}
                    </p>
                    <h4>{{ $openTask->title }}</h4>
                </div>
                <div class="agricart-task-comms__thread-actions">
                    @if ($openTask->isOpen())
                        <button type="button" class="agricart-categories__action is-primary" wire:click="completeTask">
                            {{ __('communication.tasks.complete') }}
                        </button>
                    @else
                        <button type="button" class="agricart-categories__action" wire:click="reopenTask">
                            {{ __('communication.tasks.reopen') }}
                        </button>
                    @endif
                    <button type="button" class="agricart-categories__action" wire:click="closeTask">
                        {{ __('communication.tasks.back') }}
                    </button>
                </div>
            </header>

            <ol class="agricart-task-comms__messages">
                @forelse ($messages as $message)
                    @php
                        $isMine = $authUser
                            && $message->participant
                            && (int) $message->participant->actor_id === (int) $authUser->id
                            && $message->participant->actor_type === 'user';
                    @endphp
                    <li
                        wire:key="task-msg-{{ $message->id }}"
                        @class(['agricart-task-comms__message', 'is-mine' => $isMine])
                    >
                        <p class="agricart-task-comms__meta">
                            <strong>
                                @if ($message->participant?->actor_type === 'user' && $message->participant->actor_id)
                                    {{ $authorNames[$message->participant->actor_id] ?? __('communication.tasks.staff') }}
                                @else
                                    {{ __('communication.tasks.system') }}
                                @endif
                            </strong>
                            · {{ $message->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
                        </p>
                        @if (filled($message->body))
                            <p class="agricart-task-comms__body">{{ $message->body }}</p>
                        @endif
                    </li>
                @empty
                    <li class="agricart-task-comms__empty">{{ __('communication.tasks.no_messages') }}</li>
                @endforelse
            </ol>

            <form class="agricart-task-comms__reply" wire:submit.prevent="sendReply">
                <textarea
                    rows="3"
                    class="fi-input"
                    wire:model="replyText"
                    placeholder="{{ __('communication.tasks.reply_placeholder') }}"
                ></textarea>
                @error('body') <p class="agricart-task-comms__error">{{ $message }}</p> @enderror
                <button type="submit" class="agricart-categories__action is-primary">
                    {{ __('communication.tasks.send_reply') }}
                </button>
            </form>
        </section>
    @else
        <ol class="agricart-task-comms__list">
            @forelse ($taskList as $task)
                <li wire:key="task-item-{{ $task->id }}" class="agricart-task-comms__item">
                    <button type="button" class="agricart-task-comms__item-btn" wire:click="openTask({{ $task->id }})">
                        <span @class(['agricart-task-comms__status', 'is-'.$task->status])>
                            {{ __('communication.tasks.statuses.'.$task->status) }}
                        </span>
                        <strong>{{ $task->title }}</strong>
                        <span>{{ __('communication.tasks.priorities.'.$task->priority) }}</span>
                    </button>
                </li>
            @empty
                <li class="agricart-task-comms__empty">{{ __('communication.tasks.empty') }}</li>
            @endforelse
        </ol>

        <form class="agricart-task-comms__compose" wire:submit.prevent="createTask">
            <label>
                <span>{{ __('communication.tasks.title_label') }}</span>
                <input type="text" class="fi-input" wire:model="title" maxlength="200" />
            </label>
            @error('title') <p class="agricart-task-comms__error">{{ $message }}</p> @enderror

            <label>
                <span>{{ __('communication.tasks.body_label') }}</span>
                <textarea rows="3" class="fi-input" wire:model="body" placeholder="{{ __('communication.tasks.body_placeholder') }}"></textarea>
            </label>

            <label>
                <span>{{ __('communication.tasks.priority_label') }}</span>
                <select class="fi-select" wire:model="priority">
                    @foreach ($priorities as $priorityKey)
                        <option value="{{ $priorityKey }}">{{ __('communication.tasks.priorities.'.$priorityKey) }}</option>
                    @endforeach
                </select>
            </label>

            @if ($staffOptions !== [])
                <fieldset class="agricart-task-comms__assignees">
                    <legend>{{ __('communication.tasks.assignees_label') }}</legend>
                    @foreach ($staffOptions as $staff)
                        <label wire:key="assignee-{{ $staff['id'] }}">
                            <input type="checkbox" value="{{ $staff['id'] }}" wire:model="assigneeIds" />
                            <span>{{ $staff['name'] }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @endif

            <button type="submit" class="agricart-categories__action is-primary">
                {{ __('communication.tasks.create') }}
            </button>
        </form>
    @endif
</div>
