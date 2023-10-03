you can modify windows REGISTRY to fix that,

first in regedit open this path:

HKLM\SYSTEM\CurrentControlSet\Services\Tcpip\Parameters

and create 4 new DWORD as this key and values:

TcpTimedWaitDelay
REG_DWORD: 0000001e (hex)

MaxUserPort
REG_DWORD: 0000fffe (hex)

TcpNumConnections
REG_DWORD: 00fffffe (hex)

TcpMaxDataRetransmissions
REG_DWORD: 00000005 (hex)