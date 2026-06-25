// Profilo schema (MongoDB side)
// Creates the "profiles" collection (implicitly) and a unique index on
// user_id, so each MySQL user can have at most one profile document.
//
// Run this once against your MongoDB server, e.g.:
//   mongosh profilo mongo/init.js

db = db.getSiblingDB('profilo');

db.profiles.createIndex({ user_id: 1 }, { unique: true });

print('profilo.profiles ready — unique index on user_id created.');
