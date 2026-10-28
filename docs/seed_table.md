# IT 107 Final Laboratory Project: Seed Table

**Name:** John Roucu C. bertulano
**Student No.:** 241-00304
**System:** InfoGuard: Secure Student Document Management System

Last two digits of student number: **04**

- D2 = last digit = **4**
- D1 = second-to-last digit = **0**

| Parameter | Formula | Computation | Mine |
|---|---|---|---|
| Min username length | 6 + (D1 mod 3) | 6 + (0 mod 3) = 6 + 0 | **6** |
| Max username length | 16 + (D2 mod 5) | 16 + (4 mod 5) = 16 + 4 | **20** |
| Min password length | 8 + (D2 mod 4) | 8 + (4 mod 4) = 8 + 0 | **8** |
| Max length of each name field | 20 + D2 | 20 + 4 | **24** |
| Failed log-ins before "Forgot Password?" appears | 2 + (D1 mod 2) | 2 + (0 mod 2) = 2 + 0 | **2** |
| Lockout tier 1 (seconds) | 15 + D1 | 15 + 0 | **15** |
| Lockout tier 2 (seconds) | 30 + (2 x D1) | 30 + 0 | **30** |
| Lockout tier 3 (seconds) | 60 + (4 x D1) | 60 + 0 | **60** |
| OTP expiry (minutes) | 3 + (D2 mod 3) | 3 + (4 mod 3) = 3 + 1 | **4** |
| Idle session timeout (minutes) | 5 + (D1 mod 5) | 5 + (0 mod 5) = 5 + 0 | **5** |
| IP block threshold (failures in 10 min, any account) | 8 + D2 | 8 + 4 | **12** |
| Auth question A | Q[(D2 mod 8) + 1] | Q[(4 mod 8) + 1] = Q[4 + 1] | **Q5** |
| Auth question B | Q[((D2 + 3) mod 8) + 1] | Q[(7 mod 8) + 1] = Q[7 + 1] | **Q8** |
| Auth question C | Q[((D2 + 5) mod 8) + 1] | Q[(9 mod 8) + 1] = Q[1 + 1] | **Q2** |

## My authentication questions

- **Q5:** What is your favorite hero in ML?
- **Q8:** What dish did you cook for the first time?
- **Q2:** Who was your best friend during SHS?
