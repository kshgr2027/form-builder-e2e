const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const io = new Server(server, {
    cors: { origin: '*' }
});

// Track active students: { result_id: { student_id, student_name, socket_id } }
const activeStudents = {};

io.on('connection', (socket) => {

    console.log('Connected:', socket.id);

    // ── STUDENT goes online ──────────────────────────────────────────────
    socket.on('student-online', (data) => {

        socket.role      = 'student';
        socket.result_id = data.result_id;

        activeStudents[data.result_id] = {
            student_id:   data.student_id,
            student_name: data.student_name,
            socket_id:    socket.id
        };

        socket.join(`assessment_${data.result_id}`);

        // Notify all admins
        io.to('admins').emit('session-updated', data);

        console.log('Student Online:', data);
        console.log('Active Students:', Object.keys(activeStudents).length);

    });

    // ── ADMIN joins dashboard ────────────────────────────────────────────
    socket.on('admin-join', () => {

        socket.role = 'admin';
        socket.join('admins');

        // Send current active students immediately
        socket.emit('active-sessions', activeStudents);

        console.log('Admin joined');

    });

    // ── ADMIN requests to watch a student ────────────────────────────────
    socket.on('watch-student', ({ result_id }) => {

        socket.join(`watch_${result_id}`);

        // Tell student to start streaming
        io.to(`assessment_${result_id}`).emit('start-stream', {
            result_id: result_id
        });

        console.log('Admin watching result_id:', result_id);

    });

    // ── WebRTC: Student → Admin (offer) ──────────────────────────────────
    socket.on('webrtc-offer', ({ result_id, sdp }) => {

        console.log('WebRTC offer from student, result_id:', result_id);

        io.to(`watch_${result_id}`).emit('webrtc-offer', { result_id, sdp });

    });

    // ── WebRTC: Admin → Student (answer) ─────────────────────────────────
    socket.on('webrtc-answer', ({ result_id, sdp }) => {

        console.log('WebRTC answer from admin, result_id:', result_id);

        io.to(`assessment_${result_id}`).emit('webrtc-answer', { result_id, sdp });

    });

    // ── ICE candidates (both directions) ─────────────────────────────────
    socket.on('ice-candidate', ({ result_id, candidate, from }) => {

        if (from === 'student') {
            io.to(`watch_${result_id}`).emit('ice-candidate', { result_id, candidate, from });
        } else {
            io.to(`assessment_${result_id}`).emit('ice-candidate', { result_id, candidate, from });
        }

    });

    // ── DISCONNECT ────────────────────────────────────────────────────────
    socket.on('disconnect', () => {

        if (socket.role === 'student' && socket.result_id) {
            delete activeStudents[socket.result_id];
            io.to('admins').emit('session-ended', { result_id: socket.result_id });
            io.to(`watch_${socket.result_id}`).emit('session-ended', { result_id: socket.result_id });
        }

        console.log('Disconnected:', socket.id);
        console.log('Active Students:', Object.keys(activeStudents).length);

    });

});

server.listen(3000, () => {
    console.log('WebRTC Server Running On Port 3000');
});