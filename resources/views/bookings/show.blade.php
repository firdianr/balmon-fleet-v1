<x-app-layout>
    <x-slot name="title">Detail Peminjaman {{ $booking->letter_number }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-gray-800 leading-tight">
                Detail Peminjaman: {{ $booking->letter_number }}
            </h2>
            <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-bold transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <script>
        window.masterChecklistsData = {!! json_encode($masterChecklists ?? []) !!};
    </script>

    @php
        $userId         = Auth::id();
        $userRole       = Auth::user()->role;
        $isOwner        = $booking->user_id === $userId;
        $isAdmin        = in_array($userRole, ['admin_lap', 'admin_app']);
        $isApprover     = $userRole === 'approver';
        $participants   = is_array($booking->participants) ? $booking->participants : [];
        $canOperateTrip = $isAdmin
            || $booking->driver_id == $userId
            || in_array($userId, $participants)
            || in_array((string) $userId, $participants);

        $hasAvailableActions =
            ($booking->status === 'rejected' && $isOwner) ||
            ($booking->status === 'pending' && $isOwner) ||
            (in_array($booking->status, ['pending', 'waiting', 'approved']) && $isOwner) ||
            ($booking->status === 'pending' && $isAdmin) ||
            ($booking->status === 'waiting' && $isAdmin) ||
            ($booking->status === 'waiting' && $isApprover) ||
            ($booking->status === 'approved' && $canOperateTrip) ||
            ($booking->status === 'trip_started' && ($isOwner || $isAdmin)) ||
            ($booking->status === 'on_trip' && $canOperateTrip) ||
            ($booking->status === 'trip_completed' && ($isOwner || $isAdmin)) ||
            ($booking->status === 'returned' && $isAdmin) ||
            ($booking->status === 'checked' && $isApprover);
    @endphp

    <div class="pt-2 pb-6"
        x-data="{
            booking: {{ Illuminate\Support\Js::from($booking) }},
            selectedBooking: null,
            vehicles: {{ Illuminate\Support\Js::from($availableVehicles ?? []) }},
            drivers: {{ Illuminate\Support\Js::from($availableDrivers ?? []) }},
            activeBookings: {{ Illuminate\Support\Js::from($activeBookings ?? []) }},
            attendances: {{ Illuminate\Support\Js::from($attendances ?? []) }},
            masterChecklists: window.masterChecklistsData || [],

            canOperateTrip(booking) {
                if (!booking) return false;
                const userId = {{ Auth::id() }};
                const userRole = '{{ Auth::user()->role }}';

                if (['admin_lap', 'admin_app'].includes(userRole)) return true;
                if (booking.driver_id == userId) return true;

                const participants = Array.isArray(booking.participants) ? booking.participants : [];
                return participants.includes(userId) || participants.includes(String(userId));
            },

            getAvailableVehicles() {
                if (!this.selectedBooking) return this.vehicles;

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                return (this.vehicles || []).filter(vehicle => {
                    const isConflict = (this.activeBookings || []).some(active => {
                        if (active.id === currentBookingId) return false;
                        if (active.vehicle_id !== vehicle.id) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });

                    return !isConflict;
                });
            },

            getAvailableDrivers() {
                if (!this.selectedBooking) return { primaryDrivers: this.drivers, participants: [] };

                const reqStart = new Date(this.selectedBooking.start_date);
                const reqEnd   = new Date(this.selectedBooking.end_date);
                const currentBookingId = this.selectedBooking.id;

                const freeDrivers = (this.drivers || []).filter(driver => {
                    if (driver.role !== 'driver') return false;

                    const isBooked = (this.activeBookings || []).some(active => {
                        if (String(active.id) === String(currentBookingId)) return false;
                        if (!active.driver_id || String(active.driver_id) !== String(driver.id)) return false;

                        const actStart = new Date(active.start_date);
                        const actEnd   = new Date(active.end_date);

                        return (reqStart <= actEnd && reqEnd >= actStart);
                    });

                    if (isBooked) return false;

                    const isAbsent = (this.attendances || []).some(att => {
                        if (!att.driver_id || String(att.driver_id) !== String(driver.id)) return false;

                        const attStart = new Date(att.start_date);
                        const attEnd   = new Date(att.end_date);

                        return (reqStart <= attEnd && reqEnd >= attStart);
                    });

                    return !isAbsent;
                });

                const participantsList = this.selectedBooking.participants_users || [];

                return {
                    primaryDrivers: freeDrivers,
                    participants: participantsList
                };
            },

            getInitialChecklistItems() {
                if (!Array.isArray(this.masterChecklists) || this.masterChecklists.length === 0) {
                    return [];
                }
                return this.masterChecklists.map(item => ({
                    id: item.id,
                    name: item.name,
                    category: item.category,
                    is_checked: false
                }));
            },

            checkDynamicIncidentStatus(formState) {
                const items = formState.items || [];
                const hasUncheckedItem = items.some(
                    item => ['condition', 'completeness'].includes(item.category) && !item.is_checked
                );
                formState.hasIncident = hasUncheckedItem;
            },

            // --- MODAL REVIEW ADMIN LAPANGAN ---
            showReviewModal: false,
            reviewForm: {
                action: 'approve',
                vehicle_id: '',
                driver_id: '',
                admin_note: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
            },

            openReviewModal(booking) {
                this.selectedBooking = booking;

                if (booking.participants && Array.isArray(booking.participants)) {
                    this.selectedBooking.participants_users = (this.drivers || []).filter(d =>
                        booking.participants.includes(d.id) || booking.participants.includes(String(d.id))
                    );
                } else {
                    this.selectedBooking.participants_users = [];
                }

                this.reviewForm.action = 'approve';
                this.reviewForm.vehicle_id = booking.vehicle_id ? String(booking.vehicle_id) : '';
                this.reviewForm.driver_id = booking.driver_id ? String(booking.driver_id) : '';
                this.reviewForm.admin_note = booking.admin_note || '';
                this.reviewForm.signatureError = '';
                this.reviewForm.signatureData = '';

                this.showReviewModal = true;
                this.initReviewSignaturePad();
            },

            initReviewSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.reviewSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.reviewForm.signaturePad) {
                            this.reviewForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.reviewForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearReviewSignature() {
                if (this.reviewForm.signaturePad) {
                    this.reviewForm.signaturePad.clear();
                    this.reviewForm.signatureError = '';
                    this.reviewForm.signatureData = '';
                }
            },

            submitReviewForm() {
                if (this.reviewForm.action === 'approve') {
                    const isCanvasEmpty = !this.reviewForm.signaturePad || this.reviewForm.signaturePad.isEmpty();

                    if (isCanvasEmpty) {
                        this.reviewForm.signatureError = 'Tanda tangan Admin Lapangan wajib dibubuhkan untuk menyetujui!';
                        return;
                    }

                    this.reviewForm.signatureData = this.reviewForm.signaturePad.toDataURL('image/png');
                    this.reviewForm.signatureError = '';
                } else {
                    this.reviewForm.signatureError = '';
                    this.reviewForm.signatureData = '';
                }

                this.$nextTick(() => {
                    this.$refs.reviewFormElement.submit();
                });
            },

            // --- MODAL APPROVER REVIEW ---
            showApproverModal: false,
            showChecklistDetail: false,
            approverActionType: '',
            approverForm: {
                action: 'approve',
                admin_note: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
            },

            openApproverModal(booking, type) {
                this.selectedBooking = booking;
                this.approverActionType = type;
                this.showChecklistDetail = false;
                this.approverForm.action = 'approve';
                this.approverForm.admin_note = '';
                this.approverForm.signatureError = '';
                this.approverForm.signatureData = '';

                const activeLogId = booking.log?.id;
                const allChecklists = booking.checklists || [];

                if (type === 'waiting') {
                    const vehicleChecklists = booking.vehicle?.checklists || [];
                    const lastEndChecklist = vehicleChecklists.filter(c => c.type === 'end').slice(-1)[0];
                    const fallbackChecklist = vehicleChecklists.slice(-1)[0];

                    this.selectedBooking.displayChecklists = lastEndChecklist
                        ? [lastEndChecklist]
                        : (fallbackChecklist ? [fallbackChecklist] : []);
                } else {
                    const startChecklist = allChecklists.find(c => c.type === 'start' && c.vehicle_log_id === activeLogId)
                        || allChecklists.filter(c => c.type === 'start').slice(-1)[0];

                    const endChecklist = allChecklists.find(c => c.type === 'end' && c.vehicle_log_id === activeLogId)
                        || allChecklists.filter(c => c.type === 'end').slice(-1)[0];

                    const displayList = [];
                    if (startChecklist) displayList.push(startChecklist);
                    if (endChecklist) displayList.push(endChecklist);

                    this.selectedBooking.displayChecklists = displayList;
                }

                const condNotes = booking.log?.condition_notes || {};
                this.selectedBooking.startNotes = typeof condNotes === 'object' ? condNotes.start : null;
                this.selectedBooking.endNotes   = typeof condNotes === 'object' ? condNotes.end : null;

                this.selectedBooking.parsedStartPhotos = [];
                this.selectedBooking.parsedEndPhotos   = [];

                if (booking.log && booking.log.condition_photos) {
                    let rawPhotos = booking.log.condition_photos;
                    if (typeof rawPhotos === 'string') {
                        try { rawPhotos = JSON.parse(rawPhotos); } catch (e) { rawPhotos = {}; }
                    }

                    this.selectedBooking.parsedStartPhotos = Array.isArray(rawPhotos?.start) ? rawPhotos.start : [];
                    this.selectedBooking.parsedEndPhotos   = Array.isArray(rawPhotos?.end) ? rawPhotos.end : [];
                }

                this.showApproverModal = true;
                this.initApproverSignaturePad();
            },

            initApproverSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.approverSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.approverForm.signaturePad) {
                            this.approverForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.approverForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearApproverSignature() {
                if (this.approverForm.signaturePad) {
                    this.approverForm.signaturePad.clear();
                    this.approverForm.signatureError = '';
                    this.approverForm.signatureData = '';
                }
            },

            submitApproverReviewForm() {
                if (this.approverForm.action === 'approve') {
                    const isCanvasEmpty = !this.approverForm.signaturePad || this.approverForm.signaturePad.isEmpty();

                    if (isCanvasEmpty) {
                        this.approverForm.signatureError = 'Tanda tangan pengesahan Approver wajib dibubuhkan untuk menyetujui!';
                        return;
                    }

                    this.approverForm.signatureData = this.approverForm.signaturePad.toDataURL('image/png');
                    this.approverForm.signatureError = '';
                } else {
                    this.approverForm.signatureError = '';
                    this.approverForm.signatureData = '';
                }

                this.$nextTick(() => {
                    this.$refs.approverFormElement.submit();
                });
            },

            // --- MODAL START TRIP ---
            showStartTripModal: false,
            startTripForm: {
                letter_slug: '',
                start_fuel_level: 'Full',
                items: [],
                hasIncident: false,
                notes: ''
            },

            openStartTripModal(booking) {
                this.selectedBooking = booking;
                this.startTripForm.letter_slug = booking.letter_slug;
                this.startTripForm.start_fuel_level = 'Full';
                this.startTripForm.items = this.getInitialChecklistItems();
                this.startTripForm.hasIncident = true;
                this.startTripForm.notes = '';

                this.showStartTripModal = true;
            },

            // --- MODAL TTD KEBERANGKATAN ---
            showSignDepartureModal: false,
            signDepartureForm: {
                letter_slug: '',
                signaturePad: null,
                signatureData: '',
                signatureError: ''
            },

            openSignDepartureModal(booking) {
                this.selectedBooking = booking;
                this.signDepartureForm.letter_slug = booking.letter_slug;
                this.signDepartureForm.signatureError = '';
                this.signDepartureForm.signatureData = '';
                this.showSignDepartureModal = true;

                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.signDepartureCanvas;
                        if (!canvas) return;
                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;
                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);
                        const SignaturePadClass = window.SignaturePad || SignaturePad;
                        if (!this.signDepartureForm.signaturePad) {
                            this.signDepartureForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.signDepartureForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearSignDeparture() {
                if (this.signDepartureForm.signaturePad) {
                    this.signDepartureForm.signaturePad.clear();
                    this.signDepartureForm.signatureError = '';
                }
            },

            submitSignDepartureForm() {
                if (!this.signDepartureForm.signaturePad || this.signDepartureForm.signaturePad.isEmpty()) {
                    this.signDepartureForm.signatureError = 'Tanda tangan pemohon wajib dibubuhkan!';
                    return;
                }
                this.signDepartureForm.signatureError = '';
                this.signDepartureForm.signatureData = this.signDepartureForm.signaturePad.toDataURL('image/png');
                this.$nextTick(() => {
                    this.$refs.signDepartureFormElement.submit();
                });
            },

            // --- MODAL COMPLETE TRIP ---
            showCompleteModal: false,
            completeTripForm: {
                letter_slug: '',
                end_fuel_level: 'Full',
                is_multi_day: false,
                is_washed: false,
                hasIncident: true,
                condition_notes: '',
                items: []
            },

            openCompleteModal(booking) {
                this.selectedBooking = booking;
                this.completeTripForm.letter_slug = booking.letter_slug;
                this.completeTripForm.end_fuel_level = 'Full';

                if (booking.start_date && booking.end_date) {
                    const startDateStr = booking.start_date.substring(0, 10);
                    const endDateStr = booking.end_date.substring(0, 10);
                    const start = new Date(startDateStr);
                    const end = new Date(endDateStr);
                    const diffTime = end.getTime() - start.getTime();
                    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                    this.completeTripForm.is_multi_day = diffDays >= 1;
                } else {
                    this.completeTripForm.is_multi_day = false;
                }

                this.completeTripForm.is_washed = false;
                this.completeTripForm.items = this.getInitialChecklistItems();
                this.completeTripForm.hasIncident = true;
                this.completeTripForm.condition_notes = '';

                this.showCompleteModal = true;
            },

            // --- MODAL TTD PENGEMBALIAN ---
            showSignReturnModal: false,
            signReturnForm: {
                letter_slug: '',
                return_letter_number: '',
                signaturePad: null,
                signatureData: '',
                signatureError: ''
            },

            openSignReturnModal(booking) {
                this.selectedBooking = booking;
                this.signReturnForm.letter_slug = booking.letter_slug;
                this.signReturnForm.return_letter_number = booking?.log?.return_letter_number || '';
                this.signReturnForm.signatureError = '';
                this.signReturnForm.signatureData = '';
                this.showSignReturnModal = true;

                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.signReturnCanvas;
                        if (!canvas) return;
                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;
                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);
                        const SignaturePadClass = window.SignaturePad || SignaturePad;
                        if (!this.signReturnForm.signaturePad) {
                            this.signReturnForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.signReturnForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearSignReturn() {
                if (this.signReturnForm.signaturePad) {
                    this.signReturnForm.signaturePad.clear();
                    this.signReturnForm.signatureError = '';
                }
            },

            submitSignReturnForm() {
                if (!this.signReturnForm.signaturePad || this.signReturnForm.signaturePad.isEmpty()) {
                    this.signReturnForm.signatureError = 'Tanda tangan pemohon wajib dibubuhkan!';
                    return;
                }

                this.signReturnForm.signatureError = '';
                this.signReturnForm.signatureData = this.signReturnForm.signaturePad.toDataURL('image/png');

                this.$nextTick(() => {
                    const formElement = this.$refs.signReturnFormElement;
                    if (formElement) {
                        HTMLFormElement.prototype.submit.call(formElement);
                    }
                });
            },

            // --- MODAL VERIFY RETURN (ADMIN LAPANGAN) ---
            showVerifyReturnModal: false,
            verifyReturnForm: {
                items: [],
                is_washed: false,
                wash_receipt_photo: null,
                admin_verification_notes: '',
                signaturePad: null,
                signatureData: '',
                signatureError: '',
            },

            openVerifyReturnModal(booking) {
                this.selectedBooking = booking;
                const endChk = (booking.checklists || []).find(c => c.type === 'end') || {};

                if (endChk.items && Array.isArray(endChk.items) && endChk.items.length > 0) {
                    this.verifyReturnForm.items = JSON.parse(JSON.stringify(endChk.items));
                } else {
                    this.verifyReturnForm.items = this.getInitialChecklistItems();
                }

                this.verifyReturnForm.is_washed = Boolean(endChk.is_washed);
                this.verifyReturnForm.wash_receipt_photo = endChk.wash_receipt_photo || null;
                this.verifyReturnForm.admin_verification_notes = booking.log?.admin_verification_notes || '';

                this.verifyReturnForm.signatureError = '';
                this.verifyReturnForm.signatureData = '';

                this.showVerifyReturnModal = true;
                this.initVerifyReturnSignaturePad();
            },

            initVerifyReturnSignaturePad() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const canvas = this.$refs.verifyReturnSignatureCanvas;
                        if (!canvas) return;

                        const width = canvas.offsetWidth || canvas.parentElement.clientWidth || 400;
                        const height = 144;

                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = width * ratio;
                        canvas.height = height * ratio;

                        const ctx = canvas.getContext('2d');
                        ctx.scale(ratio, ratio);

                        const SignaturePadClass = window.SignaturePad || SignaturePad;

                        if (!this.verifyReturnForm.signaturePad) {
                            this.verifyReturnForm.signaturePad = new SignaturePadClass(canvas, {
                                backgroundColor: 'rgb(255, 255, 255)',
                                penColor: 'rgb(0, 0, 128)',
                                minWidth: 1.5,
                                maxWidth: 3.5
                            });
                        } else {
                            this.verifyReturnForm.signaturePad.clear();
                        }
                    }, 150);
                });
            },

            clearVerifyReturnSignature() {
                if (this.verifyReturnForm.signaturePad) {
                    this.verifyReturnForm.signaturePad.clear();
                    this.verifyReturnForm.signatureError = '';
                    this.verifyReturnForm.signatureData = '';
                }
            },

            submitVerifyReturnForm() {
                const isCanvasEmpty = !this.verifyReturnForm.signaturePad || this.verifyReturnForm.signaturePad.isEmpty();

                if (isCanvasEmpty) {
                    this.verifyReturnForm.signatureError = 'Tanda tangan verifikasi Admin Lapangan wajib dibubuhkan!';
                    return;
                }

                this.verifyReturnForm.signatureData = this.verifyReturnForm.signaturePad.toDataURL('image/png');
                this.verifyReturnForm.signatureError = '';

                this.$nextTick(() => {
                    this.$refs.verifyReturnFormElement.submit();
                });
            },

            // --- MODAL PEMBATALAN ---
            showCancelBookingModal: false,
            cancelBookingForm: {
                letter_slug: '',
                letter_number: ''
            },
            openCancelBookingModal(booking) {
                this.cancelBookingForm.letter_slug = booking.letter_slug;
                this.cancelBookingForm.letter_number = booking.letter_number;
                this.showCancelBookingModal = true;
            },

            showCancelApprovalModal: false,
            cancelApprovalForm: {
                letter_slug: '',
                letter_number: '',
                vehicle_name: '',
                cancel_reason: ''
            },
            openCancelApprovalModal(booking) {
                this.cancelApprovalForm.letter_slug = booking.letter_slug;
                this.cancelApprovalForm.letter_number = booking.letter_number;
                this.cancelApprovalForm.vehicle_name = booking.vehicle ? `${booking.vehicle.brand} ${booking.vehicle.model} (${booking.vehicle.plate_number})` : 'Armada Operasional';
                this.cancelApprovalForm.cancel_reason = '';
                this.showCancelApprovalModal = true;
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }">
        <div class="max-w-7xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- HEADER BANNER --}}
            <div class="bg-blue-600 rounded-2xl p-6 sm:p-8 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6 overflow-hidden">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 min-w-0 w-full md:w-auto">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold text-2xl sm:text-3xl border-2 border-white/30 shadow-inner shrink-0">
                        {{ strtoupper(substr($booking->user->name ?? 'P', 0, 1)) }}
                    </div>
                    <div class="min-w-0 w-full sm:w-auto space-y-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight break-all">{{ $booking->letter_number }}</h1>
                            <span class="px-3.5 py-1 rounded-full text-xs sm:text-sm font-extrabold tracking-wider uppercase bg-white/20 backdrop-blur-md text-white border border-white/30 shrink-0">
                                Armada {{ strtoupper($booking->vehicle_type ?? 'Operasional') }}
                            </span>
                        </div>
                        <p class="text-sm sm:text-base text-blue-100 mt-1 flex items-center gap-2 flex-wrap font-medium">
                            <span>Pemohon: <strong class="text-white font-bold">{{ $booking->user->name ?? '-' }}</strong></span>
                            <span>•</span>
                            <span>NIP: {{ $booking->user->nip ?? '-' }}</span>
                            <span>•</span>
                            <span class="bg-white/15 px-3 py-0.5 rounded-full text-xs sm:text-sm font-semibold text-white">{{ $booking->user->department ?? 'Balmon Semarang' }}</span>
                        </p>
                    </div>
                </div>

                {{-- STATUS BADGE HEADER --}}
                <div class="shrink-0 self-start md:self-center">
                    @switch($booking->status)
                        @case('pending')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-yellow-500/20 border border-yellow-300/40 text-yellow-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-300 animate-bounce"></span>
                                Menunggu Review Admin
                            </span>
                            @break
                        @case('approved')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/20 border border-emerald-300/40 text-emerald-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Disetujui (Approved)
                            </span>
                            @break
                        @case('trip_started')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-500/20 border border-teal-300/40 text-teal-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-teal-300 animate-pulse"></span>
                                Menunggu TTD Keberangkatan
                            </span>
                            @break
                        @case('on_trip')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-500/20 border border-cyan-300/40 text-cyan-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-cyan-300 animate-ping"></span>
                                Dalam Perjalanan (On Trip)
                            </span>
                            @break
                        @case('trip_completed')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-500/20 border border-violet-300/40 text-violet-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-violet-300 animate-pulse"></span>
                                Menunggu TTD Pengembalian
                            </span>
                            @break
                        @case('returned')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-300/40 text-amber-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-bounce"></span>
                                Dikembalikan (Menunggu Verifikasi Admin)
                            </span>
                            @break
                        @case('checked')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-500/20 border border-indigo-300/40 text-indigo-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                                Diverifikasi Admin
                            </span>
                            @break
                        @case('completed')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-500/20 border border-blue-300/40 text-blue-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <svg class="w-5 h-5 text-blue-300 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Selesai (Completed)
                            </span>
                            @break
                        @case('rejected')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-rose-500/20 border border-rose-300/40 text-rose-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                                Ditolak
                            </span>
                            @break
                        @case('canceled')
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-500/20 border border-gray-300/40 text-gray-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                                Dibatalkan
                            </span>
                            @break
                        @default
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500/20 border border-amber-300/40 text-amber-100 font-bold text-sm sm:text-base backdrop-blur-md">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-bounce"></span>
                                Menunggu Persetujuan
                            </span>
                    @endswitch
                </div>
            </div>

            {{-- PANEL AKSI OPERASIONAL (MUNCUL DINAMIS SESUAI ROLE & STATUS) --}}
            @if($hasAvailableActions)
                <div class="bg-white rounded-2xl border border-indigo-200/80 shadow-xs p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-900">Tindakan / Aksi Operasional</h3>
                            <p class="text-xs text-gray-500">Pilih tindakan lanjutan sesuai tahapan peminjaman kendaraan saat ini</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        {{-- 1. PERBAIKI PERMOHONAN DITOLAK (PEMOHON) --}}
                        {{-- @if($booking->status === 'rejected' && $isOwner)
                            <a href="{{ route('bookings.edit', $booking->letter_slug) }}"
                               class="px-4 py-2.5 border border-amber-500/50 text-amber-700 bg-amber-50 hover:bg-amber-100 font-bold rounded-xl text-xs sm:text-sm inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0 text-amber-600" viewBox="0 0 24 24">
                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                </svg>
                                <span>Perbaiki Permohonan</span>
                            </a>
                        @endif --}}

                        {{-- 2. EDIT PERMOHONAN PENDING (PEMOHON) --}}
                        {{-- @if($booking->status === 'pending' && $isOwner)
                            <a href="{{ route('bookings.edit', $booking->letter_slug) }}"
                               class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-bold rounded-xl text-xs sm:text-sm inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                                <span>Edit Permohonan</span>
                            </a>
                        @endif --}}

                        {{-- 3. ISI LOG KEBERANGKATAN (DRIVER / ADMIN / TIM) --}}
                        @if($booking->status === 'approved' && $canOperateTrip)
                            <button type="button"
                                    @click="openStartTripModal(booking)"
                                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                    <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z"/>
                                </svg>
                                <span>Isi Log Keberangkatan</span>
                            </button>
                        @endif

                        {{-- 4. TTD KEBERANGKATAN (PEMOHON / ADMIN LAPANGAN) --}}
                        @if($booking->status === 'trip_started' && ($isOwner || $isAdmin))
                            <button type="button"
                                    @click="openSignDepartureModal(booking)"
                                    class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs animate-pulse inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                                <span>TTD Keberangkatan</span>
                            </button>
                        @endif

                        {{-- 5. ISI LOG KEPULANGAN (DRIVER / ADMIN / TIM) --}}
                        @if($booking->status === 'on_trip' && $canOperateTrip)
                            <button type="button"
                                    @click="openCompleteModal(booking)"
                                    class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                <span>Isi Log Kepulangan</span>
                            </button>
                        @endif

                        {{-- 6. TTD PENGEMBALIAN (PEMOHON / ADMIN LAPANGAN) --}}
                        @if($booking->status === 'trip_completed' && ($isOwner || $isAdmin))
                            <button type="button"
                                    @click="openSignReturnModal(booking)"
                                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs animate-pulse inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                                </svg>
                                <span>TTD Pengembalian</span>
                            </button>
                        @endif

                        {{-- 7. AKSI ADMIN LAPANGAN --}}
                        @if($isAdmin)
                            @if($booking->status === 'pending')
                                <button type="button"
                                        @click="openReviewModal(booking)"
                                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                        <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                    </svg>
                                    <span>Tinjau & Plot Armada</span>
                                </button>
                            @endif

                            @if($booking->status === 'waiting')
                                <button type="button"
                                        @click="openReviewModal(booking)"
                                        class="px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold rounded-xl text-xs sm:text-sm inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                        <path d="M3 3v5h5"/>
                                        <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                        <path d="M16 16h5v5"/>
                                    </svg>
                                    <span>Re-assign Armada</span>
                                </button>
                                <button type="button"
                                        @click="openCancelApprovalModal(booking)"
                                        class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs sm:text-sm inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    <span>Batalkan Persetujuan</span>
                                </button>
                            @endif

                            @if($booking->status === 'returned')
                                <button type="button"
                                        @click="openVerifyReturnModal(booking)"
                                        class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 active:bg-cyan-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                        <path d="m9 14 2 2 4-4"/>
                                    </svg>
                                    <span>Tinjau Kepulangan</span>
                                </button>
                            @endif
                        @endif

                        {{-- 8. AKSI APPROVER --}}
                        @if($isApprover)
                            @if($booking->status === 'waiting')
                                <button type="button"
                                        @click="openApproverModal(booking, 'waiting')"
                                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                        <path d="m9 14 2 2 4-4"/>
                                    </svg>
                                    <span>Tinjau & Setujui</span>
                                </button>
                            @endif

                            @if($booking->status === 'checked')
                                <button type="button"
                                        @click="openApproverModal(booking, 'checked')"
                                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs inline-flex items-center gap-2 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <path d="m9 15 2 2 4-4"/>
                                    </svg>
                                    <span>Sahkan Pengembalian</span>
                                </button>
                            @endif
                        @endif

                        {{-- 9. BATALKAN PENGAJUAN (PEMOHON) --}}
                        @if($isOwner && in_array($booking->status, ['pending', 'waiting', 'approved']))
                            <button type="button"
                                    @click="openCancelBookingModal(booking)"
                                    class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs sm:text-sm inline-flex items-center gap-2 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                </svg>
                                <span>Batalkan Pengajuan</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- MAIN CONTENT GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                {{-- KOLOM KIRI --}}
                <div class="lg:col-span-7 space-y-6">

                    {{-- CARD 1: INFORMASI RINCIAN PERJALANAN --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between flex-wrap border-b border-gray-100 pb-4 gap-3">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-bold text-gray-900">Rincian Permohonan Dinas</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Informasi utama jadwal dan maksud perjalanan</p>
                                </div>
                            </div>

                            {{-- TOMBOL SURAT DINAS & SURAT TUGAS KONDISIONAL --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($booking->letter_type === 'nota_dinas')
                                    {{-- 1. Tombol Lihat Nota Dinas (Utama) --}}
                                    @if($booking->letter_file)
                                        <a href="{{ route('bookings.files.letter', $booking->letter_slug) }}" target="_blank" 
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs sm:text-sm font-bold transition border border-indigo-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            <span>Lihat Nota Dinas</span>
                                        </a>
                                    @endif

                                    {{-- 2. Tombol Lihat Surat Tugas (Aktif jika sudah di-upload saat pengembalian, Disabled jika belum) --}}
                                    @if($booking->return_letter_file)
                                        <a href="{{ route('bookings.files.return_letter', $booking->letter_slug) }}" target="_blank" 
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-50 text-amber-800 hover:bg-amber-100 rounded-xl text-xs sm:text-sm font-bold transition border border-amber-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                            <span>Lihat Surat Tugas</span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 text-gray-400 rounded-xl text-xs sm:text-sm font-bold border border-gray-200 cursor-not-allowed" 
                                            title="Surat Tugas belum diunggah (diisi saat pengembalian)">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                            <span>Surat Tugas (Belum Ada)</span>
                                        </span>
                                    @endif

                                @else
                                    {{-- Jika Jenis Surat dari awal adalah Surat Tugas Murni (Hanya 1 Tombol) --}}
                                    @if($booking->letter_file)
                                        <a href="{{ route('bookings.files.letter', $booking->letter_slug) }}" target="_blank" 
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs sm:text-sm font-bold transition border border-indigo-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="7 10 12 15 17 10"/>
                                                <line x1="12" y1="15" x2="12" y2="3"/>
                                            </svg>
                                            <span>Lihat Surat Tugas</span>
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Waktu Berangkat</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-bold text-gray-900 flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span>{{ $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Waktu Kembali</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-bold text-gray-900 flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                        <line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/>
                                        <line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <span>{{ $booking->end_date ? \Carbon\Carbon::parse($booking->end_date)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
                                </div>
                            </div>

                            <div class="sm:col-span-2 space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Tujuan Perjalanan</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-bold text-gray-900 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span>{{ $booking->destination }}</span>
                                </div>
                            </div>

                            <div class="sm:col-span-2 space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-500 block">Keperluan / Maksud Tugas</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-medium text-gray-800 leading-relaxed">
                                    {{ $booking->purpose }}
                                </div>
                            </div>

                            @if($booking->vehicle_requirements)
                                <div class="sm:col-span-2 space-y-1.5">
                                    <label class="text-xs sm:text-sm font-bold text-gray-500 block">Spesifikasi / Kebutuhan Tambahan Armada</label>
                                    <div class="p-3.5 bg-amber-50/70 rounded-xl border border-amber-200 text-xs sm:text-sm font-semibold text-amber-900">
                                        {{ $booking->vehicle_requirements }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 2: ANGGOTA TIM / PENGIKUT DINAS --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-4">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-gray-900">Anggota Tim / Pengikut Dinas</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Personel terdaftar dalam perjalanan dinas ini</p>
                            </div>
                        </div>

                        @php
                            $participantUsers = [];
                            if (!empty($booking->participants)) {
                                $participantUsers = \App\Models\User::whereIn('id', $booking->participants)->get();
                            }
                        @endphp

                        @if(count($participantUsers) > 0)
                            <div class="divide-y divide-gray-100">
                                @foreach($participantUsers as $idx => $pUser)
                                    <div class="py-3.5 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3.5">
                                            <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center border border-indigo-100 shrink-0">
                                                {{ $idx + 1 }}
                                            </div>
                                            <div>
                                                <p class="text-xs sm:text-sm font-bold text-gray-900">{{ $pUser->name }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    NIP: {{ $pUser->nip ?? '-' }} • {{ $pUser->jabatan ?? '-' }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-[11px] font-bold uppercase tracking-wider">
                                            {{ $pUser->role }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs sm:text-sm text-gray-400 italic py-2">Tidak ada anggota tim tambahan (Hanya Pemohon Utama).</p>
                        @endif
                    </div>

                    {{-- CARD 3: LOG OPERASIONAL BBM & SPEEDOMETER --}}
                    @php $log = $booking->log; @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="20" x2="12" y2="10"/>
                                        <line x1="18" y1="20" x2="18" y2="4"/>
                                        <line x1="6" y1="20" x2="6" y2="16"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-bold text-gray-900">Data Operational Log & Indikator BBM</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Waktu check-in/out dan bukti foto speedometer</p>
                                </div>
                            </div>
                        </div>

                        @if($log)
                            <div class="space-y-6 text-xs sm:text-sm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- DATA KEBERANGKATAN --}}
                                    <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100 space-y-3 flex flex-col justify-between">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between border-b border-emerald-200/60 pb-2.5">
                                                <span class="font-bold text-emerald-900 text-xs sm:text-sm flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                                    </svg>
                                                    <span>Keberangkatan</span>
                                                </span>
                                                <span class="text-xs text-emerald-700 font-bold">
                                                    {{ $log->checked_out_at ? \Carbon\Carbon::parse($log->checked_out_at)->locale('id')->translatedFormat('d M Y, H:i') : '-' }} WIB
                                                </span>
                                            </div>

                                            <div class="text-gray-800">
                                                <span class="text-gray-500 block text-xs font-semibold">BBM Awal:</span>
                                                <span class="font-bold text-sm sm:text-base text-gray-900">{{ $log->start_fuel_level ?? '-' }}</span>
                                            </div>
                                        </div>

                                        @if($log->start_photo)
                                            <div class="pt-2">
                                                <span class="text-gray-500 block text-xs font-semibold mb-1">Foto BBM Awal:</span>
                                                <a href="{{ asset('storage/' . $log->start_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-emerald-200 bg-gray-100 h-36">
                                                    <img src="{{ asset('storage/' . $log->start_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Awal">
                                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                                                        Klik untuk Memperbesar
                                                    </div>
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- DATA PENGEMBALIAN --}}
                                    <div class="p-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-3 flex flex-col justify-between">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2.5">
                                                <span class="font-bold text-indigo-900 text-xs sm:text-sm flex items-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                        <line x1="4" y1="22" x2="4" y2="15"/>
                                                    </svg>
                                                    <span>Pengembalian</span>
                                                </span>
                                                <span class="text-xs text-indigo-700 font-bold">
                                                    {{ $log->checked_in_at ? \Carbon\Carbon::parse($log->checked_in_at)->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : 'Dalam Perjalanan' }}
                                                </span>
                                            </div>

                                            @if($log->checked_in_at)
                                                <div class="text-gray-800">
                                                    <span class="text-gray-500 block text-xs font-semibold">BBM Akhir:</span>
                                                    <span class="font-bold text-sm sm:text-base text-gray-900">{{ $log->end_fuel_level ?? '-' }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if($log->checked_in_at && $log->end_photo)
                                            <div class="pt-2">
                                                <span class="text-gray-500 block text-xs font-semibold mb-1">Foto BBM Pengembalian:</span>
                                                <a href="{{ asset('storage/' . $log->end_photo) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-indigo-200 bg-gray-100 h-36">
                                                    <img src="{{ asset('storage/' . $log->end_photo) }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200" alt="Foto Akhir">
                                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">
                                                        Klik untuk Memperbesar
                                                    </div>
                                                </a>
                                            </div>
                                        @elseif(!$log->checked_in_at)
                                            <div class="py-8 text-center text-gray-400 italic text-xs sm:text-sm">
                                                Perjalanan dinas sedang berlangsung. Data pengembalian belum diinput.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-xs sm:text-sm text-gray-400 italic py-4 text-center bg-gray-50 rounded-xl border border-gray-100">
                                Kendaraan belum melakukan proses keberangkatan (check-out).
                            </p>
                        @endif
                    </div>

                    {{-- CARD 4: RINCIAN TRIP CHECKLIST DINAMIS (PRE-TRIP & POST-TRIP) + CATATAN KERUSAKAN --}}
                    @if($startChecklist || $endChecklist || ($booking->log && ($booking->log->condition_notes || !empty($booking->log->condition_photos))))
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-6">
                            
                            {{-- HEADER CARD 4 --}}
                            <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                                <div class="p-2.5 bg-slate-100 text-slate-700 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-slate-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                        <path d="m9 14 2 2 4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-bold text-gray-900">Hasil Physical Check Armada (Checklist)</h2>
                                    <p class="text-xs sm:text-sm text-gray-500">Kondisi fisik detail dan kelengkapan dinas</p>
                                </div>
                            </div>

                            <div class="space-y-5">
                                
                                {{-- 1. CHECKLIST KEBERANGKATAN (PRE-TRIP) --}}
                                @if($startChecklist)
                                    @php
                                        $startItems = is_array($startChecklist->items) ? $startChecklist->items : json_decode($startChecklist->items ?? '[]', true);
                                        $startCompleteness = array_filter($startItems, fn($i) => ($i['category'] ?? '') === 'completeness');
                                        $startConditions   = array_filter($startItems, fn($i) => ($i['category'] ?? '') === 'condition');
                                    @endphp

                                    <div class="px-4 py-4 rounded-xl border border-emerald-200 bg-white space-y-4 shadow-2xs">
                                        <div class="flex justify-between items-center border-b border-gray-100 pb-2.5">
                                            <span class="font-bold text-xs uppercase px-3 py-1 rounded-lg tracking-wide flex items-center gap-1.5 bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-emerald-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                                                    <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-3.05 11a22.35 22.35 0 0 1-3.95 2z"/>
                                                </svg>
                                                <span>Keberangkatan (Pre-Trip)</span>
                                            </span>
                                        </div>

                                        {{-- Kelengkapan Dinas --}}
                                        @if(count($startCompleteness) > 0)
                                            <div class="space-y-2">
                                                <span class="font-bold text-gray-800 text-xs sm:text-sm border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                    </svg>
                                                    <span>Kelengkapan Dinas:</span>
                                                </span>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                    @foreach($startCompleteness as $cItem)
                                                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                            <span>{{ $cItem['name'] }}</span>
                                                            <span class="font-bold flex items-center gap-1.5 {{ !empty($cItem['is_checked']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                @if(!empty($cItem['is_checked']))
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    <span>Ada / Lengkap</span>
                                                                @else
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                                    <span>Tidak Ada</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Kondisi Fisik Kendaraan --}}
                                        @if(count($startConditions) > 0)
                                            <div class="space-y-2 pt-1">
                                                <span class="font-bold text-gray-800 text-xs sm:text-sm border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="11" cy="11" r="8"/>
                                                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                                    </svg>
                                                    <span>Kondisi Fisik Kendaraan:</span>
                                                </span>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                    @foreach($startConditions as $condItem)
                                                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                            <span>{{ $condItem['name'] }}</span>
                                                            <span class="font-bold flex items-center gap-1.5 {{ !empty($condItem['is_checked']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                @if(!empty($condItem['is_checked']))
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    <span>Baik / Normal</span>
                                                                @else
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                                    <span>Bermasalah</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- 2. CHECKLIST KEPULANGAN (POST-TRIP) --}}
                                @if($endChecklist)
                                    @php
                                        $endItems = is_array($endChecklist->items) ? $endChecklist->items : json_decode($endChecklist->items ?? '[]', true);
                                        $endCompleteness = array_filter($endItems, fn($i) => ($i['category'] ?? '') === 'completeness');
                                        $endConditions   = array_filter($endItems, fn($i) => ($i['category'] ?? '') === 'condition');
                                    @endphp

                                    <div class="px-4 py-4 rounded-xl border border-indigo-200 bg-white space-y-4 shadow-2xs">
                                        <div class="flex justify-between items-center border-b border-gray-100 pb-2.5">
                                            <span class="font-bold text-xs uppercase px-3 py-1 rounded-lg tracking-wide flex items-center gap-1.5 bg-indigo-100 text-indigo-800 border border-indigo-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-indigo-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                                    <line x1="4" y1="22" x2="4" y2="15"/>
                                                </svg>
                                                <span>Pengembalian (Post-Trip)</span>
                                            </span>
                                            
                                            @if($endChecklist->is_washed)
                                                <span class="text-xs font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200 flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3z"/>
                                                    </svg>
                                                    <span>Dikembalikan Bersih</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Kelengkapan Dinas --}}
                                        @if(count($endCompleteness) > 0)
                                            <div class="space-y-2">
                                                <span class="font-bold text-gray-800 text-xs sm:text-sm border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                    </svg>
                                                    <span>Kelengkapan Dinas:</span>
                                                </span>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                    @foreach($endCompleteness as $cItem)
                                                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                            <span>{{ $cItem['name'] }}</span>
                                                            <span class="font-bold flex items-center gap-1.5 {{ !empty($cItem['is_checked']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                @if(!empty($cItem['is_checked']))
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    <span>Ada / Lengkap</span>
                                                                @else
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                                    <span>Tidak Ada</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Kondisi Fisik Kendaraan --}}
                                        @if(count($endConditions) > 0)
                                            <div class="space-y-2 pt-1">
                                                <span class="font-bold text-gray-800 text-xs sm:text-sm border-b border-gray-100 pb-1 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="11" cy="11" r="8"/>
                                                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                                    </svg>
                                                    <span>Kondisi Fisik Kendaraan:</span>
                                                </span>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-gray-700 text-xs sm:text-sm">
                                                    @foreach($endConditions as $condItem)
                                                        <div class="flex items-center justify-between py-1 border-b border-gray-50">
                                                            <span>{{ $condItem['name'] }}</span>
                                                            <span class="font-bold flex items-center gap-1.5 {{ !empty($condItem['is_checked']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                @if(!empty($condItem['is_checked']))
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                                    <span>Baik / Normal</span>
                                                                @else
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                                    <span>Bermasalah</span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Lampiran Nota Cuci Mobil --}}
                                        @if($endChecklist->wash_receipt_photo)
                                            <div class="pt-2 border-t border-gray-100 space-y-2">
                                                <span class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                    <span>Lampiran Bukti Nota Cuci Mobil:</span>
                                                </span>
                                                <a href="{{ asset('storage/' . $endChecklist->wash_receipt_photo) }}" target="_blank" class="inline-block">
                                                    <img src="{{ asset('storage/' . $endChecklist->wash_receipt_photo) }}" class="h-32 w-auto object-cover rounded-xl border border-amber-300 hover:opacity-90 transition shadow-2xs">
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- 3. CATATAN KONDISI / INSIDEN KERUSAKAN SELAMA PERJALANAN --}}
                                @if($booking->log && ($booking->log->condition_notes || !empty($booking->log->condition_photos)))
                                    @php
                                        $log = $booking->log;
                                        
                                        $startNotes = $log->condition_notes['start'] ?? null;
                                        $endNotes   = $log->condition_notes['end'] ?? null;

                                        $photosPayload = $log->condition_photos ?? [];
                                        $startPhotos   = is_array($photosPayload) ? ($photosPayload['start'] ?? []) : [];
                                        $endPhotos     = is_array($photosPayload) ? ($photosPayload['end'] ?? []) : [];
                                        $allPhotos     = array_merge($startPhotos, $endPhotos);
                                    @endphp

                                    <div class="p-4 bg-rose-50 rounded-xl border border-rose-200 space-y-3">
                                        <span class="font-bold text-rose-900 text-xs sm:text-sm flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-rose-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                <line x1="12" y1="9" x2="12" y2="13"/>
                                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                            <span>Catatan Kondisi / Kerusakan Selama Perjalanan</span>
                                        </span>

                                        {{-- Catatan Teks --}}
                                        @if($startNotes || $endNotes)
                                            <div class="space-y-2">
                                                @if($startNotes)
                                                    <div class="p-3 bg-white rounded-lg border border-rose-200">
                                                        <span class="text-xs font-bold text-rose-800 block mb-0.5">Catatan Keberangkatan (Pre-Trip):</span>
                                                        <p class="text-xs sm:text-sm text-rose-950 leading-relaxed font-medium">{{ $startNotes }}</p>
                                                    </div>
                                                @endif

                                                @if($endNotes)
                                                    <div class="p-3 bg-white rounded-lg border border-rose-200">
                                                        <span class="text-xs font-bold text-rose-800 block mb-0.5">Catatan Kepulangan (Post-Trip):</span>
                                                        <p class="text-xs sm:text-sm text-rose-950 leading-relaxed font-medium">{{ $endNotes }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Foto Kerusakan / Insiden (Dipisah Start & End) --}}
                                        @if(count($startPhotos) > 0 || count($endPhotos) > 0)
                                            <div class="space-y-3 pt-2">
                                                {{-- Foto Kerusakan Keberangkatan --}}
                                                @if(count($startPhotos) > 0)
                                                    <div class="space-y-1.5">
                                                        <span class="text-xs font-bold text-rose-800 block">Foto Bukti Kerusakan / Insiden Keberangkatan (Pre-Trip):</span>
                                                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                            @foreach($startPhotos as $photoPath)
                                                                <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="block rounded-lg overflow-hidden border border-rose-200 h-24 bg-gray-100 hover:opacity-90 transition">
                                                                    <img src="{{ asset('storage/' . $photoPath) }}" class="w-full h-full object-cover" alt="Foto Kerusakan Pre-Trip">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Foto Kerusakan Pengembalian --}}
                                                @if(count($endPhotos) > 0)
                                                    <div class="space-y-1.5">
                                                        <span class="text-xs font-bold text-rose-800 block">Foto Bukti Kerusakan / Insiden Kepulangan (Post-Trip):</span>
                                                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                            @foreach($endPhotos as $photoPath)
                                                                <a href="{{ asset('storage/' . $photoPath) }}" target="_blank" class="block rounded-lg overflow-hidden border border-rose-200 h-24 bg-gray-100 hover:opacity-90 transition">
                                                                    <img src="{{ asset('storage/' . $photoPath) }}" class="w-full h-full object-cover" alt="Foto Kerusakan Post-Trip">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endif

                </div>

                {{-- KOLOM KANAN --}}
                <div class="lg:col-span-5 space-y-6">

                    {{-- CARD 5: UNIT KENDARAAN & DRIVER --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-4">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A2 2 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                    <circle cx="7" cy="17" r="2"/>
                                    <path d="M9 17h6"/>
                                    <circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-gray-900">Unit Kendaraan & Driver</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Informasi armada dan penunjukan pengemudi</p>
                            </div>
                        </div>

                        @if($booking->vehicle)
                            <div class="py-4 px-4 bg-indigo-50/50 rounded-xl border border-indigo-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider">{{ $booking->vehicle->type ?? 'Armada' }}</span>
                                    <span class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs sm:text-sm font-extrabold tracking-widest font-mono shadow-2xs">
                                        {{ $booking->vehicle->plate_number }}
                                    </span>
                                </div>

                                <div>
                                    <p class="text-base sm:text-lg font-extrabold text-gray-900">{{ $booking->vehicle->brand }} {{ $booking->vehicle->model }}</p>
                                    @if(!empty($booking->vehicle->color))
                                        <p class="text-xs sm:text-sm text-gray-600 font-medium mt-0.5">Warna: {{ $booking->vehicle->color }}</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="px-4 py-4 bg-amber-50 rounded-xl border border-amber-200/80 text-amber-900 text-xs sm:text-sm leading-relaxed space-y-1">
                                <p class="font-bold flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-amber-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>Penentuan Unit Belum Di-Plot</span>
                                </p>
                                <p class="text-amber-800">Peminjaman tipe <strong>Operasional</strong> akan ditentukan oleh Admin Lapangan sebelum jadwal keberangkatan.</p>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-gray-100">
                            <span class="text-xs sm:text-sm font-bold text-gray-600 block mb-2">Driver / Pengemudi Ditunjuk:</span>
                            @if($booking->driver)
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-current stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm font-bold text-gray-900">{{ $booking->driver->name }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $booking->driver->phone ?? 'Tidak ada No WA' }}</p>
                                        </div>
                                    </div>
                                    @if($booking->driver->phone)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->driver->phone) }}" target="_blank" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold rounded-lg transition border border-emerald-200 flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 fill-none stroke-emerald-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            <span>Chat WA</span>
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm font-bold text-gray-700">
                                    Belum Ditetapkan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- CARD 6: CATATAN VERIFIKASI & DOKUMEN CETAK BAST --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-6 sm:p-7 space-y-5">
                        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-gray-900">Catatan Verifikasi & Berkas Cetak</h2>
                                <p class="text-xs sm:text-sm text-gray-500">Histori persetujuan dan pengunduhan dokumen cetak</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-xs sm:text-sm font-bold text-gray-600 block">Catatan Admin / Verifikator</label>
                                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs sm:text-sm text-gray-700 italic leading-relaxed font-medium">
                                    {{ $booking->admin_note ?? 'Belum ada catatan administratif.' }}
                                </div>
                            </div>

                            <div class="space-y-2.5 pt-3 border-t border-gray-100">
                                <label class="text-xs sm:text-sm font-bold text-gray-800 block">Cetak Dokumen & Berita Acara (BAST):</label>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs sm:text-sm">
                                    <a href="{{ route('bookings.pdf.permohonan', $booking->letter_slug) }}" target="_blank"
                                    class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 font-bold rounded-xl border border-gray-200 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-600 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span>Formulir Peminjaman</span>
                                    </a>

                                    @if(in_array($booking->status, ['waiting', 'approved', 'trip_started', 'on_trip','trip_completed', 'returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.bast_peminjaman', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl border border-emerald-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-emerald-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Peminjaman</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Peminjaman</span>
                                        </button>
                                    @endif

                                    @if(in_array($booking->status, ['returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.formulir_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold rounded-xl border border-indigo-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-indigo-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span>Formulir Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span>Formulir Pengembalian</span>
                                        </button>
                                    @endif

                                    @if(in_array($booking->status, ['returned', 'checked', 'completed']))
                                        <a href="{{ route('bookings.pdf.bast_pengembalian', $booking->letter_slug) }}" target="_blank" 
                                        class="flex items-center gap-2 px-3.5 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold rounded-xl border border-blue-200 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-blue-700 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Pengembalian</span>
                                        </a>
                                    @else
                                        <button disabled class="flex items-center gap-2 px-3.5 py-2.5 bg-gray-100 text-gray-400 font-bold rounded-xl border border-gray-200 cursor-not-allowed w-full text-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-none stroke-gray-400 stroke-2 shrink-0" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                            <span>BAST Pengembalian</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        {{-- IMPOR SELURUH MODAL OPERASIONAL --}}
        @include('bookings.modals.review')
        @include('bookings.modals.approver')
        @include('bookings.modals.verify-return')
        @include('bookings.modals.start-trip')
        @include('bookings.modals.complete-trip')
        @include('bookings.modals.sign-departure')
        @include('bookings.modals.sign-return')
        @include('bookings.modals.cancel-booking')
        @include('bookings.modals.cancel-approval')
    </div>
</x-app-layout>