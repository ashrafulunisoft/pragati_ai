┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ cd /home/ashraful/Unisoft/VMSUCBL/VMSUCBL/vms-ucbl && git branch -a
* main
  remotes/origin/HEAD -> origin/main
  remotes/origin/backend
  remotes/origin/dev
  remotes/origin/frontend
  remotes/origin/main
                                                                                                                             
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL/VMSUCBL/vms-ucbl]
└─$ cd "/home/ashraful/Unisoft/VMSUCBL"
                                                                                                                             
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ ls -la ~/.ssh/
total 24
drwx------  3 ashraful ashraful 4096 Jan 20 01:00 .
drwx------ 24 ashraful ashraful 4096 Jan 20 23:12 ..
drwx------  2 ashraful ashraful 4096 Jan 20 22:42 agent
-rw-rw-r--  1 ashraful ashraful   61 Jan 20 00:59 config
-rw-------  1 ashraful ashraful  978 Jan 20 01:00 known_hosts
-rw-r--r--  1 ashraful ashraful  142 Jan 20 01:00 known_hosts.old
                                                                                                                             
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ ssh-keygen -t ed25519 -C "ashrafulunisoft" -f ~/.ssh/github_ashrafulunisoft
Generating public/private ed25519 key pair.
Enter passphrase for "/home/ashraful/.ssh/github_ashrafulunisoft" (empty for no passphrase): 
Enter same passphrase again: 
Passphrases do not match.  Try again.
Enter passphrase for "/home/ashraful/.ssh/github_ashrafulunisoft" (empty for no passphrase): 
Enter same passphrase again: 
Your identification has been saved in /home/ashraful/.ssh/github_ashrafulunisoft
Your public key has been saved in /home/ashraful/.ssh/github_ashrafulunisoft.pub
The key fingerprint is:
SHA256:4Gf8DXNt+7iFvvkcoX1D2fjW5SljhR6S8uWjhbdBI6U ashrafulunisoft
The key's randomart image is:
+--[ED25519 256]--+
|                 |
|                 |
|      .      .   |
|     . o    +...o|
|      . S.oE.*+=o|
|       o .o=O.B==|
|          .o.%o=B|
|            =.*B+|
|           . .*++|
+----[SHA256]-----+
                                                                                                                         
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ cat ~/.ssh/config
Host 202.74.246.122
  HostName 202.74.246.122
  User ai_root
                                                                                                                         
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ cat >> ~/.ssh/config << 'EOF'
heredoc> 
heredoc> Host github.com
heredoc>   HostName github.com
heredoc>   User git
heredoc>   IdentityFile ~/.ssh/github_ashrafulunisoft
heredoc> EOF
                                                                                                                         
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ eval "$(ssh-agent -s)" && ssh-add ~/.ssh/github_ashrafulunisoft
Agent pid 15997
Identity added: /home/ashraful/.ssh/github_ashrafulunisoft (ashrafulunisoft)
                                                                                                                         
┌──(ashraful㉿kali)-[~/Unisoft/VMSUCBL]
└─$ cat ~/.ssh/github_ashrafulunisoft.pub
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJFbw+RQlLG+Y1hHFz38wA01zCoZ3JOl9dHMKtobvFDi ashrafulunisoft
                                            
